<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\DossierRecouvrement;
use App\Domain\Finances\Models\SituationRecouvrement;
use App\Domain\Finances\Repositories\DossierRecouvrementRepository;
use App\Domain\Finances\Types\DossierStatut;
use App\Domain\Finances\Types\NatureCreance;
use App\Support\AnneeScolaireContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconstruit les situations de recouvrement à partir des données sources
 * (inscriptions, souscriptions, abonnements, details d'encaissement...).
 *
 * Deux modes :
 *  - recalculerTout()       : reconstruit la photo du jour pour toutes les
 *                             inscriptions actives (appelé par le job nocturne).
 *  - recalculerUne()        : recalcule une seule inscription + nature.
 */
class RecouvrementService
{
    /** Seuil à partir duquel un dossier est ouvert automatiquement. */
    public const SEUIL_MONTANT_DOSSIER = 5000;   // FCFA (paramétrable)
    public const SEUIL_JOURS_DOSSIER   = 30;     // jours de retard minimum

    public function __construct(
        private DossierRecouvrementRepository $dossiers,
        private EngagementService $engagements,
    ) {}

    /**
     * Reconstruit la photo du jour pour toutes les inscriptions actives.
     * Écrase les enregistrements existants pour la même date.
     */
    public function recalculerTout(?int $anneeId = null): array
    {
        $anneeId ??= app(AnneeScolaireContext::class)->id();
        $dateCalcul = now()->toDateString();

        $inscriptionsActives = $this->inscriptionsActives($anneeId);

        $stats = ['situations' => 0, 'dossiers_ouverts' => 0, 'dossiers_maj' => 0];

        DB::transaction(function () use ($inscriptionsActives, $anneeId, $dateCalcul, &$stats) {
            foreach ($inscriptionsActives as $inscription) {
                // Les engagements de l'inscription sont lus une fois pour toutes ses natures
                $dus = $this->engagements->duParNature($inscription->id);

                foreach ($this->naturesApplicables($inscription) as $nature) {
                    $situation = $this->recalculerSituation(
                        inscriptionId: $inscription->id,
                        nature: $nature,
                        anneeId: $anneeId,
                        dateCalcul: $dateCalcul,
                        montantDu: $dus[$nature] ?? null,
                    );

                    if (! $situation) continue;

                    $stats['situations']++;

                    // Ouvre / met à jour le dossier si seuil atteint
                    if ($this->doitOuvrirDossier($situation)) {
                        $resultat = $this->ouvrirOuMajDossier($inscription->id, $nature, $situation, $anneeId);
                        if ($resultat === 'ouvert') $stats['dossiers_ouverts']++;
                        if ($resultat === 'maj')    $stats['dossiers_maj']++;
                    }
                }
            }
        });

        Log::info('Recouvrement recalculé', $stats + ['date' => $dateCalcul, 'annee_id' => $anneeId]);

        return $stats;
    }

    /** Recalcule une seule situation (inscription + nature). */
    public function recalculerUne(int $inscriptionId, string $nature, ?string $dateCalcul = null): ?SituationRecouvrement
    {
        $anneeId    = app(AnneeScolaireContext::class)->id();
        $dateCalcul ??= now()->toDateString();

        return $this->recalculerSituation($inscriptionId, $nature, $anneeId, $dateCalcul);
    }

    /* =================================================================
       Internals
       ================================================================= */

    private function recalculerSituation(
        int $inscriptionId,
        string $nature,
        ?int $anneeId,
        string $dateCalcul,
        ?float $montantDu = null,
    ): ?SituationRecouvrement {
        // 1) Montant dû par cette inscription pour cette nature (souscription,
        //    abonnement de service, ou montants portés par l'inscription).
        $montantDu ??= $this->calculerMontantDu($inscriptionId, $nature);

        if ($montantDu <= 0) {
            // Rien de dû : on supprime la situation du jour si elle existe.
            SituationRecouvrement::query()
                ->where('inscription_id', $inscriptionId)
                ->where('nature', $nature)
                ->where('date_calcul', $dateCalcul)
                ->delete();
            return null;
        }

        // 2) Récupère le montant déjà payé (encaissements effectifs).
        $montantPaye = $this->calculerMontantPaye($inscriptionId, $nature);

        // 3) Calcule le retard et l'ancienneté.
        $retard = round($montantDu - $montantPaye, 2);
        $retard = max(0, $retard);

        $joursRetard = $retard > 0
            ? $this->calculerJoursRetard($inscriptionId, $nature)
            : 0;

        // 4) Upsert sur (inscription, nature, date)
        return SituationRecouvrement::updateOrCreate(
            [
                'inscription_id' => $inscriptionId,
                'nature'         => $nature,
                'date_calcul'    => $dateCalcul,
            ],
            [
                'montant_du'   => round($montantDu, 2),
                'montant_paye' => round($montantPaye, 2),
                'retard'       => $retard,
                'jours_retard' => $joursRetard,
                'annee_id'     => $anneeId,
                'etat'         => 1,
            ]
        );
    }

    /* ---- Sources de calcul : EngagementService, partagé avec l'écran Paiements ---- */

    private function calculerMontantDu(int $inscriptionId, string $nature): float
    {
        return $this->engagements->montantDu($inscriptionId, $nature);
    }

    /** Encaissements effectifs pour une inscription + nature. */
    private function calculerMontantPaye(int $inscriptionId, string $nature): float
    {
        return $this->engagements->montantPaye($inscriptionId, $nature);
    }

    /**
     * Ancienneté du retard, en jours, comptée depuis la date d'inscription
     * (faute de date d'échéance par nature dans le schéma actuel).
     */
    private function calculerJoursRetard(int $inscriptionId, string $nature): int
    {
        $dateReference = DB::table('inscriptions')
            ->where('id', $inscriptionId)
            ->value('date_inscription');

        if (! $dateReference) return 0;

        $date = Carbon::parse($dateReference)->startOfDay();

        return $date->isFuture() ? 0 : (int) $date->diffInDays(now()->startOfDay());
    }

    /* ---- Ouverture / mise à jour de dossier ---- */

    private function doitOuvrirDossier(SituationRecouvrement $situation): bool
    {
        return $situation->retard >= self::SEUIL_MONTANT_DOSSIER
            && $situation->jours_retard >= self::SEUIL_JOURS_DOSSIER;
    }

    /**
     * @return string 'ouvert' | 'maj' | 'ignore'
     */
    private function ouvrirOuMajDossier(
        int $inscriptionId,
        string $nature,
        SituationRecouvrement $situation,
        ?int $anneeId
    ): string {
        $dossierExistant = $this->dossiers->dossierOuvertPour($inscriptionId, $nature);

        if ($dossierExistant) {
            // On met juste à jour le montant recouvré / statut si soldé.
            $nouveauMontantRecouvre = $situation->montant_paye;

            if ($nouveauMontantRecouvre >= $dossierExistant->montant_initial_du) {
                // Le dossier est soldé : on le clôture automatiquement.
                $this->dossiers->update($dossierExistant->id, [
                    'montant_recouvre' => $nouveauMontantRecouvre,
                    'statut'           => DossierStatut::SOLDE->value,
                    'date_cloture'     => now()->toDateString(),
                    'motif_cloture'    => 'Encaissement intégral détecté automatiquement',
                ]);
                return 'maj';
            }

            if (abs((float) $dossierExistant->montant_recouvre - $nouveauMontantRecouvre) > 0.01) {
                $this->dossiers->update($dossierExistant->id, [
                    'montant_recouvre' => $nouveauMontantRecouvre,
                ]);
                return 'maj';
            }

            return 'ignore';
        }

        // Création
        $this->dossiers->create([
            'inscription_id'     => $inscriptionId,
            'nature'             => $nature,
            'montant_initial_du' => $situation->retard,
            'montant_recouvre'   => $situation->montant_paye,
            'statut'             => DossierStatut::EN_COURS->value,
            'date_ouverture'     => now()->toDateString(),
            'annee_id'           => $anneeId,
        ]);

        return 'ouvert';
    }

    /* ---- Récupération des inscriptions / natures à traiter ---- */

    private function inscriptionsActives(?int $anneeId): \Illuminate\Support\Collection
    {
        return DB::table('inscriptions')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->where('etat', 1)
            ->get(['id', 'eleve_id', 'annee_id', 'date_inscription']);
    }

    private function naturesApplicables(object $inscription): array
    {
        return array_map(fn (NatureCreance $nature) => $nature->value, EngagementService::NATURES);
    }
}