<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\DossierRecouvrement;
use App\Domain\Finances\Models\Relance;
use App\Domain\Finances\Repositories\DossierRecouvrementRepository;
use App\Domain\Finances\Types\DossierStatut;
use App\Domain\Finances\Types\ResultatRelance;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RelanceService
{
    public function __construct(
        private DossierRecouvrementRepository $dossiers,
    ) {}

    /**
     * Enregistre une action de relance.
     * Règles :
     *  - dossier non clos
     *  - date_relance <= aujourd'hui
     *  - si resultat = paiement_total, clôture automatique du dossier
     */
    public function enregistrer(
        int $dossierId,
        string $typeRelance,
        string $dateRelance,
        ?string $contenu,
        ?string $resultat,
        ?string $dateProchaine,
        ?int $userId,
        ?float $montantDuMoment = null,
        ?int $messageId = null,
    ): Relance {
        return DB::transaction(function () use (
            $dossierId, $typeRelance, $dateRelance, $contenu, $resultat,
            $dateProchaine, $userId, $montantDuMoment, $messageId
        ) {
            $dossier = $this->dossiers->findOrFail($dossierId);

            if ($dossier->isClos()) {
                throw new RuntimeException(
                    "Ce dossier est déjà clos ({$dossier->statut->label()}) — aucune relance possible."
                );
            }

            $relance = Relance::create([
                'dossier_recouvrement_id' => $dossier->id,
                'type_relance'            => $typeRelance,
                'date_relance'            => $dateRelance,
                'contenu'                 => $contenu,
                'montant_du_moment'       => $montantDuMoment ?? $dossier->montant_restant,
                'resultat'                => $resultat,
                'date_prochaine_relance'  => $dateProchaine,
                'utilisateur_id'          => $userId,
                'message_id'              => $messageId,
                'annee_id'                => $dossier->annee_id,
            ]);

            // Cas particuliers : ajustement du dossier selon le résultat.
            if ($resultat === ResultatRelance::PAIEMENT_TOTAL->value) {
                $this->dossiers->update($dossier->id, [
                    'montant_recouvre' => $dossier->montant_initial_du,
                    'statut'           => DossierStatut::SOLDE->value,
                    'date_cloture'     => now()->toDateString(),
                    'motif_cloture'    => 'Paiement total constaté lors de la relance',
                ]);
            } elseif ($resultat === ResultatRelance::PROMESSE_PAIEMENT->value) {
                $this->dossiers->update($dossier->id, [
                    'statut' => DossierStatut::ECHEANCIER->value,
                ]);
            }

            return $relance;
        });
    }

    /** Change le statut d'un dossier (contentieux, irrécouvrable...). */
    public function changerStatut(int $dossierId, string $nouveauStatut, ?string $motif = null): DossierRecouvrement
    {
        $dossier = $this->dossiers->findOrFail($dossierId);

        $statut = DossierStatut::from($nouveauStatut);

        $payload = ['statut' => $statut->value];

        if ($statut->isClos()) {
            $payload['date_cloture']  = now()->toDateString();
            $payload['motif_cloture'] = $motif;
        }

        $this->dossiers->update($dossier->id, $payload);

        return $dossier->refresh();
    }
}