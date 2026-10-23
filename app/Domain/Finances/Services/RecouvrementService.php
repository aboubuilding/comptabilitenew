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
 * Recouvrement : les dossiers des élèves en retard sur les plans d'échéancier.
 *
 *  - recalculerTout() : pour l'année, chaque élève qui n'a pas réglé une
 *    échéance dont la date butoir est passée a un dossier ouvert (montant
 *    échu, payé, restant) ; un élève revenu à jour voit son dossier soldé.
 *    Lancé par le bouton « Recalculer » et chaque nuit à 2 h.
 *  - recalculerUne()  : photo d'une seule inscription + nature (engagements).
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
     * Dossiers de recouvrement de l'année : un dossier par élève qui doit une
     * échéance échue (date passée, non couverte) — scolarité des plans
     * d'échéancier, mois de cantine ou de transport. Le dossier liste ses
     * échéances réclamées et le montant réclamé ; il est repris à chaque
     * recalcul. Quand l'élève est revenu à jour, son dossier est clôturé et
     * garde le paiement qui l'a soldé. Relançable sans doublon.
     *
     * @return array{echus:int, dossiers_ouverts:int, dossiers_maj:int, dossiers_soldes:int, retard:float}
     */
    public function recalculerTout(?int $anneeId = null, ?\DateTimeInterface $jour = null): array
    {
        $anneeId ??= app(AnneeScolaireContext::class)->id();
        $jour = \Illuminate\Support\Carbon::instance($jour ?? now())->startOfDay();
        $dateCalcul = $jour->toDateString();

        $eleves = app(EcheancesEleveService::class)->elevesNonAJour((int) $anneeId, $jour);
        $stats = [
            'echus' => $eleves->count(), 'dossiers_ouverts' => 0, 'dossiers_maj' => 0, 'dossiers_soldes' => 0,
            'retard' => round($eleves->sum('reste'), 2),
        ];

        DB::transaction(function () use ($eleves, $anneeId, $jour, $dateCalcul, &$stats) {
            $enRetard = [];

            foreach ($eleves as $eleve) {
                $inscriptionId = (int) $eleve['inscription_id'];
                $enRetard[] = $inscriptionId;
                $natures = $eleve['natures'];
                $naturePrincipale = in_array('scolarite', $natures, true) ? 'scolarite' : $natures[0];

                // Photo du jour, par nature
                foreach (collect($eleve['echeances'])->groupBy('nature') as $nature => $echeances) {
                    $premiere = $echeances->min(fn ($e) => $e['date']);
                    SituationRecouvrement::updateOrCreate(
                        ['inscription_id' => $inscriptionId, 'nature' => $nature, 'date_calcul' => $dateCalcul],
                        ['montant_du' => round($echeances->sum('montant'), 2), 'montant_paye' => round($echeances->sum('paye'), 2),
                         'retard' => round($echeances->sum('reste'), 2),
                         'jours_retard' => $premiere ? (int) $premiere->copy()->startOfDay()->diffInDays($jour) : 0,
                         'annee_id' => $anneeId, 'etat' => 1]
                    );
                }

                // Un seul dossier par élève (toutes natures)
                $dossier = $this->dossiers->dossierOuvertPour($inscriptionId);
                $valeurs = ['montant_initial_du' => $eleve['montant'], 'montant_recouvre' => $eleve['paye'], 'nature' => $naturePrincipale];
                if (! $dossier) {
                    $dossier = $this->dossiers->create($valeurs + [
                        'inscription_id' => $inscriptionId,
                        'statut'         => DossierStatut::EN_COURS->value,
                        'date_ouverture' => $dateCalcul,
                        'annee_id'       => $anneeId,
                    ]);
                    $stats['dossiers_ouverts']++;
                } elseif (abs((float) $dossier->montant_initial_du - $eleve['montant']) > 0.01
                    || abs((float) $dossier->montant_recouvre - $eleve['paye']) > 0.01) {
                    $this->dossiers->update($dossier->id, $valeurs);
                    $stats['dossiers_maj']++;
                }

                // Échéances réclamées : reprises à chaque recalcul
                DB::table('dossier_recouvrement_echeances')->where('dossier_recouvrement_id', $dossier->id)->delete();
                DB::table('dossier_recouvrement_echeances')->insert(array_map(fn ($e) => [
                    'dossier_recouvrement_id' => $dossier->id,
                    'nature'        => $e['nature'],
                    'libelle'       => $e['libelle'],
                    'date_echeance' => $e['date']?->toDateString(),
                    'montant'       => $e['montant'],
                    'montant_paye'  => $e['paye'],
                    'reste'         => $e['reste'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ], $eleve['echeances']));
            }

            // Élèves revenus à jour : dossier clôturé, avec le paiement qui l'a soldé
            $aSolder = DossierRecouvrement::query()
                ->where('etat', 1)
                ->where('annee_id', $anneeId)
                ->whereNotIn('statut', [DossierStatut::SOLDE->value, DossierStatut::IRRECOUVRABLE->value])
                ->when($enRetard !== [], fn ($q) => $q->whereNotIn('inscription_id', $enRetard))
                ->get();
            foreach ($aSolder as $dossier) {
                $this->dossiers->update($dossier->id, [
                    'montant_recouvre'    => $dossier->montant_initial_du,
                    'statut'              => DossierStatut::SOLDE->value,
                    'date_cloture'        => $dateCalcul,
                    'motif_cloture'       => 'À jour de ses échéances au recalcul',
                    'paiement_cloture_id' => $this->paiementDeCloture($dossier),
                ]);
                DB::table('dossier_recouvrement_echeances')->where('dossier_recouvrement_id', $dossier->id)
                    ->update(['montant_paye' => DB::raw('montant'), 'reste' => 0, 'updated_at' => now()]);
                $stats['dossiers_soldes']++;
            }
        });

        Log::info('Recouvrement recalculé', $stats + ['date' => $dateCalcul, 'annee_id' => $anneeId]);

        return $stats;
    }

    /**
     * Paiement qui a soldé le dossier : le dernier encaissement de l'élève sur
     * les natures réclamées (depuis l'ouverture du dossier, à défaut le dernier).
     */
    private function paiementDeCloture(DossierRecouvrement $dossier): ?int
    {
        $types = DB::table('dossier_recouvrement_echeances')->where('dossier_recouvrement_id', $dossier->id)
            ->distinct()->pluck('nature')
            ->map(fn ($n) => ['scolarite' => \App\Domain\Finances\Types\TypeDetail::FRAIS_SCOLAIRE, 'cantine' => \App\Domain\Finances\Types\TypeDetail::CANTINE,
                              'bus' => \App\Domain\Finances\Types\TypeDetail::BUS][$n]->value ?? null)
            ->filter()->values()->all();

        $requete = fn () => DB::table('details as d')
            ->where('d.inscription_id', $dossier->inscription_id)
            ->where('d.etat', 1)
            ->where('d.statut_paiement', \App\Domain\Finances\Types\PaiementStatut::ENCAISSE->value)
            ->when($types !== [], fn ($q) => $q->whereIn('d.type_paiement', $types))
            ->whereNotNull('d.paiement_id')
            ->orderByRaw('COALESCE(d.date_encaissement, d.date_paiement, d.created_at) DESC')->orderByDesc('d.id');

        $depuis = $dossier->date_ouverture?->toDateString();

        return ($depuis ? $requete()->whereRaw('COALESCE(d.date_encaissement, d.date_paiement, DATE(d.created_at)) >= ?', [$depuis])->value('d.paiement_id') : null)
            ?? $requete()->value('d.paiement_id');
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