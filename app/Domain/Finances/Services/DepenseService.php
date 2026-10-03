<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\DepenseData;
use App\Domain\Finances\Models\Depense;
use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Repositories\DepenseRepository;
use App\Domain\Finances\Types\DepenseStatut;
use App\Support\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DepenseService
{
    public function __construct(
        private DepenseRepository $depenses,
        private CaisseRepository  $caisses,
        private FileUploadService $files,
    ) {}

    /** Saisie d'une dépense avec pièce justificative optionnelle. */
   // app/Domain/Finances/Services/DepenseService.php — méthode saisir() révisée

public function saisir(
    DepenseData $data,
    ?UploadedFile $justificatif = null,
    ?string $natureAnalytique = null, // ex. 'fournitures', 'entretien'...
): Depense {
    return DB::transaction(function () use ($data, $justificatif, $natureAnalytique) {
        $payload = [
            'libelle'        => $data->libelle,
            'beneficiaire'   => $data->beneficiaire,
            'motif_depense'  => $data->motif,
            'date_depense'   => $data->dateDepense,
            'montant'        => $data->montant,
            'utilisateur_id' => $data->auteurId,
            'statut_depense' => DepenseStatut::EN_ATTENTE->value,
        ];

        if ($justificatif) {
            $payload['justificatif_path']         = $this->files->store($justificatif, 'depenses/justificatifs');
            $payload['justificatif_nom_original'] = $justificatif->getClientOriginalName();
        }

        $depense = $this->depenses->create($payload);

        // Si une nature analytique est fournie ET qu'une règle existe,
        // on ventile automatiquement. En cas d'absence de règle ou
        // d'incohérence de configuration, on n'échoue pas la création :
        // l'utilisateur ventile manuellement plus tard.
        if ($natureAnalytique) {
            try {
                app(\App\Domain\Finances\Services\VentilationAnalytiqueService::class)
                    ->appliquerReglesAuto(
                        source: $depense,
                        nature: $natureAnalytique,
                        sens: \App\Domain\Finances\Types\SensAnalytique::DEPENSE->value,
                        montantSource: (float) $depense->montant,
                        userId: $data->auteurId,
                    );
            } catch (\Throwable $e) {
                \Log::info('Ventilation auto non appliquée', [
                    'depense_id' => $depense->id,
                    'nature'     => $natureAnalytique,
                    'raison'     => $e->getMessage(),
                ]);
                // Aucun throw : la dépense reste créée, la ventilation est manquante.
            }
        }

        return $depense;
    });
}

    /** Modification (autorisée tant que la dépense est en attente). */
    public function modifier(int $id, DepenseData $data, ?UploadedFile $justificatif = null): Depense
    {
        return DB::transaction(function () use ($id, $data, $justificatif) {
            $depense = $this->depenses->findOrFail($id);

            if (! $depense->isEnAttente()) {
                throw new RuntimeException(
                    "Seule une dépense en attente peut être modifiée."
                );
            }

            $payload = [
                'libelle'       => $data->libelle,
                'beneficiaire'  => $data->beneficiaire,
                'motif_depense' => $data->motif,
                'date_depense'  => $data->dateDepense,
                'montant'       => $data->montant,
            ];

            if ($justificatif) {
                // Supprime l'ancien fichier si présent
                $this->files->delete($depense->justificatif_path);

                $payload['justificatif_path']         = $this->files->store($justificatif, 'depenses/justificatifs');
                $payload['justificatif_nom_original'] = $justificatif->getClientOriginalName();
            }

            $this->depenses->update($depense->id, $payload);

            return $depense->refresh();
        });
    }

    /** Validation par la Direction (dépense en attente uniquement). */
    public function valider(int $id, int $valideurId): Depense
    {
        return DB::transaction(function () use ($id, $valideurId) {
            $depense = $this->depenses->findOrFail($id);

            if (! $depense->isEnAttente()) {
                throw new RuntimeException(
                    "Seule une dépense en attente peut être validée."
                );
            }

            $this->depenses->update($depense->id, [
                'statut_depense'  => DepenseStatut::VALIDEE->value,
                'valide_par'      => $valideurId,
                'date_validation' => now(),
                'motif_rejet'     => null,
            ]);

            return $depense->refresh();
        });
    }

    /** Rejet par la Direction — motif obligatoire. */
    public function rejeter(int $id, int $valideurId, string $motif): Depense
    {
        if (blank($motif)) {
            throw new RuntimeException("Le motif de rejet est obligatoire.");
        }

        return DB::transaction(function () use ($id, $valideurId, $motif) {
            $depense = $this->depenses->findOrFail($id);

            if (! $depense->isEnAttente()) {
                throw new RuntimeException(
                    "Seule une dépense en attente peut être rejetée."
                );
            }

            $this->depenses->update($depense->id, [
                'statut_depense'  => DepenseStatut::REJETEE->value,
                'valide_par'      => $valideurId,
                'date_validation' => now(),
                'motif_rejet'     => $motif,
            ]);

            return $depense->refresh();
        });
    }

    /**
     * Règlement d'une dépense validée via une caisse ouverte.
     * Crée automatiquement le mouvement de caisse (sortie) associé.
     */
    public function reglerViaCaisse(int $id, int $caisseId, int $regleurId): Depense
    {
        return DB::transaction(function () use ($id, $caisseId, $regleurId) {
            $depense = $this->depenses->findOrFail($id);
            $caisse  = $this->caisses->findOrFail($caisseId);

            if (! $depense->isValidee()) {
                throw new RuntimeException(
                    "Seule une dépense validée peut être réglée via une caisse."
                );
            }

            if ($depense->isReglee()) {
                throw new RuntimeException("Cette dépense est déjà réglée.");
            }

            if (! $caisse->isOuverte()) {
                throw new RuntimeException(
                    "La caisse sélectionnée est clôturée : impossible d'y rattacher un règlement."
                );
            }

            // Cohérence d'année (le BaseRepository injecte annee_id depuis
            // AnneeScolaireContext — on refuse de régler une dépense d'une
            // autre année).
            $anneeActive = app(\App\Support\AnneeScolaireContext::class)->id();
            if ($anneeActive !== null && (int) $depense->annee_id !== $anneeActive) {
                throw new RuntimeException(
                    "Cette dépense appartient à une autre année scolaire : elle ne peut plus être réglée."
                );
            }

            // 1) Marque la dépense comme réglée
            $this->depenses->update($depense->id, [
                'caisse_id'      => $caisse->id,
                'date_reglement' => now(),
                'regle_par'      => $regleurId,
            ]);

            // 2) Crée le mouvement de sortie associé sur la caisse
            \App\Domain\Finances\Models\Mouvement::create([
                'libelle'          => "Règlement dépense : {$depense->libelle}",
                'beneficiaire'     => $depense->beneficiaire,
                'motif'            => $depense->motif_depense,
                'date_mouvement'   => now()->toDateString(),
                'montant'          => (float) $depense->montant,
                'type_mouvement'   => \App\Domain\Finances\Types\MouvementType::DEPENSE->value,
                'caisse_id'        => $caisse->id,
                'utilisateur_id'   => $regleurId,
                'depense_id'       => $depense->id,
                'annee_id'         => $caisse->annee_id,
                'statut_mouvement' => 1,
                'etat'             => 1,
            ]);

            return $depense->refresh();
        });
    }
}