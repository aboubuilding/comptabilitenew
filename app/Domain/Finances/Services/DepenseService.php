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
            'ligne_budgetaire_id' => $data->ligneBudgetaireId,
            'utilisateur_id' => $data->auteurId,
            'statut_depense' => DepenseStatut::EN_ATTENTE->value,
        ];

        // Sous le seuil, la dépense est validée d'office (sans valideur) : elle peut aussitôt être décaissée
        if (self::valideeDOffice($data->montant)) {
            $payload['statut_depense']  = DepenseStatut::VALIDEE->value;
            $payload['date_validation'] = now();
        }

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
                'ligne_budgetaire_id' => $data->ligneBudgetaireId,
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
     * Joint (ou remplace) le justificatif d'une dépense, à tout moment — y compris
     * après son décaissement. L'ancien fichier est supprimé.
     */
    public function joindreJustificatif(int $id, UploadedFile $fichier): Depense
    {
        $depense = $this->depenses->findOrFail($id);
        if ($depense->isRejetee()) {
            throw new RuntimeException("Cette dépense a été rejetée : on n'y joint plus de justificatif.");
        }

        $ancien = $depense->justificatif_path;
        $this->depenses->update($depense->id, [
            'justificatif_path'         => $this->files->store($fichier, 'depenses/justificatifs'),
            'justificatif_nom_original' => $fichier->getClientOriginalName(),
        ]);
        $this->files->delete($ancien);

        return $depense->refresh();
    }

    /** Décaissements de caisse (mouvements « Dépense en espèces ») qui ne portent encore aucune dépense. */
    public function decaissementsSansDepense(?int $anneeId): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Domain\Finances\Models\Mouvement::query()
            ->where('etat', 1)
            ->where('type_mouvement', \App\Domain\Finances\Types\MouvementType::DEPENSE->value)
            ->whereNull('depense_id')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v));
    }

    /**
     * Crée les dépenses des décaissements déjà effectués en caisse qui n'en ont
     * pas (base historique) : une dépense par mouvement, déjà décaissée — validée,
     * réglée dans la caisse du mouvement, par son auteur, pour son montant — et le
     * mouvement y est rattaché. Relançable : un mouvement rattaché n'est plus repris.
     *
     * @return array{creees:int, montant:float}
     */
    public function genererDepuisDecaissements(?int $anneeId): array
    {
        return DB::transaction(function () use ($anneeId) {
            $creees = 0;
            $montant = 0.0;

            foreach ($this->decaissementsSansDepense($anneeId)->orderBy('date_mouvement')->orderBy('id')->lockForUpdate()->get() as $m) {
                $date = $m->date_mouvement ?? $m->created_at;
                $depense = Depense::create([
                    'libelle'          => $m->libelle ?: 'Décaissement de caisse',
                    'beneficiaire'     => $m->beneficiaire,
                    'motif_depense'    => $m->motif,
                    'date_depense'     => $date,
                    'montant'          => (int) round((float) $m->montant),
                    'utilisateur_id'   => $m->utilisateur_id,
                    'statut_depense'   => DepenseStatut::VALIDEE->value,
                    'date_validation'  => $date,
                    'caisse_id'        => $m->caisse_id,
                    'date_reglement'   => $date,
                    'regle_par'        => $m->utilisateur_id,
                    'montant_decaisse' => round((float) $m->montant, 2),
                    'annee_id'         => $m->annee_id,
                    'etat'             => 1,
                ]);
                $m->forceFill(['depense_id' => $depense->id])->save();

                $creees++;
                $montant += (float) $m->montant;
            }

            return ['creees' => $creees, 'montant' => round($montant, 2)];
        });
    }

    /** Une dépense sous le seuil de validation (config finances) est validée dès sa saisie. */
    public static function valideeDOffice(float $montant): bool
    {
        return $montant < (float) config('finances.seuil_validation_depense', 150000);
    }

    /**
     * Règlement d'une dépense validée via une caisse ouverte.
     * Crée automatiquement le mouvement de caisse (sortie) associé, pour le
     * montant réellement décaissé (par défaut, le montant de la dépense).
     */
    public function reglerViaCaisse(int $id, int $caisseId, int $regleurId, ?float $montantDecaisse = null): Depense
    {
        if ($montantDecaisse !== null && $montantDecaisse <= 0) {
            throw new RuntimeException("Le montant décaissé doit être strictement positif.");
        }

        return DB::transaction(function () use ($id, $caisseId, $regleurId, $montantDecaisse) {
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

            $montant = round($montantDecaisse ?? (float) $depense->montant, 2);

            // 1) Marque la dépense comme réglée
            $this->depenses->update($depense->id, [
                'caisse_id'        => $caisse->id,
                'date_reglement'   => now(),
                'regle_par'        => $regleurId,
                'montant_decaisse' => $montant,
            ]);

            // 2) Crée le mouvement de sortie associé sur la caisse
            \App\Domain\Finances\Models\Mouvement::create([
                'libelle'          => "Règlement dépense : {$depense->libelle}",
                'beneficiaire'     => $depense->beneficiaire,
                'motif'            => $depense->motif_depense,
                'date_mouvement'   => now()->toDateString(),
                'montant'          => $montant,
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