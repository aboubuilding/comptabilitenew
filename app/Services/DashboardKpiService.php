<?php

namespace App\Services;

use App\Repositories\Eloquent\InscriptionRepository;
use App\Repositories\Eloquent\DetailRepository;
use App\Repositories\Eloquent\VenteRepository;
use App\Repositories\Eloquent\DepenseRepository;
use App\Repositories\Eloquent\AbonnementBusRepository;
use App\Repositories\Eloquent\InscriptionCantineRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardKpiService
{
    protected ?int $anneeId;
    protected InscriptionRepository $inscriptionRepo;
    protected DetailRepository $detailRepo;
    protected VenteRepository $venteRepo;
    protected DepenseRepository $depenseRepo;
    protected AbonnementBusRepository $busRepo;
    protected InscriptionCantineRepository $cantineRepo;

    public function __construct(
        InscriptionRepository $inscriptionRepo,
        DetailRepository $detailRepo,
        VenteRepository $venteRepo,
        DepenseRepository $depenseRepo,
        AbonnementBusRepository $busRepo,
        InscriptionCantineRepository $cantineRepo
    ) {
        $this->inscriptionRepo = $inscriptionRepo;
        $this->detailRepo = $detailRepo;
        $this->venteRepo = $venteRepo;
        $this->depenseRepo = $depenseRepo;
        $this->busRepo = $busRepo;
        $this->cantineRepo = $cantineRepo;
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Récupère tous les KPI en un seul appel (avec cache optionnel)
     */
    public function getAllKpi(): array
    {
        $cacheKey = 'dashboard_kpi_' . $this->anneeId . '_' . date('Y-m-d');
        return Cache::remember($cacheKey, 3600, function () {
            return [
                'inscriptions' => $this->getInscriptionsKpi(),
                'financiers'   => $this->getFinanciersKpi(),
                'paiements'    => $this->getPaiementsKpi(),
                'cantine'      => $this->getCantineKpi(),
                'bus'          => $this->getBusKpi(),
                'ventes'       => $this->getVentesKpi(),
                'depenses'     => $this->getDepensesKpi(),
                'abandons'     => $this->getAbandonsKpi(),
                'frais_supp'   => $this->getFraisSupplementairesKpi(),
            ];
        });
    }

    /**
     * KPI inscriptions
     */
    private function getInscriptionsKpi(): array
    {
        $query = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $this->anneeId);
        $total = $query->count();

        $garcons = (clone $query)->whereHas('eleve', fn($q) => $q->where('sexe', 1))->count();
        $filles = (clone $query)->whereHas('eleve', fn($q) => $q->where('sexe', 0))->count();

        $evolution = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mois'), DB::raw('count(*) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $parNiveau = $this->inscriptionRepo->activeQuery()
            ->with('niveau')
            ->where('annee_id', $this->anneeId)
            ->select('niveau_id', DB::raw('count(*) as total'))
            ->groupBy('niveau_id')
            ->get()
            ->map(fn($i) => ['niveau' => $i->niveau?->libelle, 'total' => $i->total]);

        return [
            'total_inscrits'     => $total,
            'garcons'            => $garcons,
            'filles'             => $filles,
            'evolution_mensuelle'=> $evolution,
            'repartition_niveau' => $parNiveau,
        ];
    }

    /**
     * KPI financiers globaux (scolarité principalement)
     */
    private function getFinanciersKpi(): array
    {
        $totalPrevu = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->sum(DB::raw('COALESCE(frais_scolarite,0) + COALESCE(frais_inscription,0) + COALESCE(frais_assurance,0)'));

        $totalEncaissé = $this->detailRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_paiement', 1)
            ->where('statut_paiement', 1)
            ->sum('montant');

        $tauxRecouvrement = $totalPrevu > 0 ? round(($totalEncaissé / $totalPrevu) * 100, 2) : 0;
        $impayes = max($totalPrevu - $totalEncaissé, 0);

        return [
            'total_prevu'        => $totalPrevu,
            'total_encaisse'     => $totalEncaissé,
            'taux_recouvrement'  => $tauxRecouvrement,
            'impayes'            => $impayes,
        ];
    }

    /**
     * KPI paiements (tous types)
     */
    private function getPaiementsKpi(): array
    {
        // Répartition par mode de paiement (jointure avec paiements)
        $parMode = $this->detailRepo->getModel()->newQuery()
            ->join('paiements', 'details.paiement_id', '=', 'paiements.id')
            ->where('details.annee_id', $this->anneeId)
            ->where('details.statut_paiement', 1)
            ->select('paiements.mode_paiement', DB::raw('SUM(details.montant) as total'))
            ->groupBy('paiements.mode_paiement')
            ->get()
            ->map(fn($p) => ['mode' => $this->getModeLabel($p->mode_paiement), 'total' => $p->total]);

        $evolution = $this->detailRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut_paiement', 1)
            ->select(DB::raw('DATE_FORMAT(date_encaissement, "%Y-%m") as mois'), DB::raw('SUM(montant) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $enAttente = $this->detailRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut_paiement', 0)
            ->count();

        return [
            'repartition_mode'   => $parMode,
            'evolution_mensuelle'=> $evolution,
            'paiements_attente'  => $enAttente,
        ];
    }

    /**
     * KPI cantine
     */
    private function getCantineKpi(): array
    {
        $inscrits = $this->cantineRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->count();

        $totalDu = $this->cantineRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->sum('montant_total_du');

        $totalPaye = $this->cantineRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->get()
            ->sum(fn($i) => $i->montant_paye);

        $tauxRecouvrement = $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 2) : 0;

        return [
            'inscrits'          => $inscrits,
            'total_du'          => $totalDu,
            'total_paye'        => $totalPaye,
            'taux_recouvrement' => $tauxRecouvrement,
        ];
    }

    /**
     * KPI bus
     */
    private function getBusKpi(): array
    {
        $inscrits = $this->busRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->count();

        $totalDu = $this->busRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->sum('montant_total_du');

        $totalPaye = $this->busRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->get()
            ->sum(fn($b) => $b->montant_paye);

        return [
            'inscrits'    => $inscrits,
            'total_du'    => $totalDu,
            'total_paye'  => $totalPaye,
            'reste'       => $totalDu - $totalPaye,
        ];
    }

    /**
     * KPI ventes boutique
     */
    private function getVentesKpi(): array
    {
        $totalCA = $this->venteRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->sum('montant_total');

        $evolution = $this->venteRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->select(DB::raw('DATE_FORMAT(date_vente, "%Y-%m") as mois'), DB::raw('SUM(montant_total) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $topProduits = $this->venteRepo->activeQuery()
            ->with('produit')
            ->where('annee_id', $this->anneeId)
            ->select('produit_id', DB::raw('SUM(quantite) as quantite_totale'))
            ->groupBy('produit_id')
            ->orderBy('quantite_totale', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($v) => ['produit' => $v->produit?->libelle, 'quantite' => $v->quantite_totale]);

        return [
            'chiffre_affaires' => $totalCA,
            'evolution'        => $evolution,
            'top_produits'     => $topProduits,
        ];
    }

    /**
     * KPI dépenses (achats, salaires, etc.)
     */
    private function getDepensesKpi(): array
    {
        $totalDepenses = $this->depenseRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->sum('montant');

        $parMotif = $this->depenseRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->select('motif', DB::raw('SUM(montant) as total'))
            ->groupBy('motif')
            ->get();

        $evolution = $this->depenseRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->select(DB::raw('DATE_FORMAT(date_depense, "%Y-%m") as mois'), DB::raw('SUM(montant) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        return [
            'total_depenses' => $totalDepenses,
            'par_motif'      => $parMotif,
            'evolution'      => $evolution,
        ];
    }

    /**
     * KPI abandons
     */
    private function getAbandonsKpi(): array
    {
        $totalAbandons = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut_abandon', 1)
            ->count();

        $parNiveau = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut_abandon', 1)
            ->with('niveau')
            ->select('niveau_id', DB::raw('count(*) as total'))
            ->groupBy('niveau_id')
            ->get()
            ->map(fn($i) => ['niveau' => $i->niveau?->libelle, 'total' => $i->total]);

        $totalInscrits = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->count();
        $tauxAbandon = $totalInscrits > 0 ? round(($totalAbandons / $totalInscrits) * 100, 2) : 0;

        return [
            'total_abandons' => $totalAbandons,
            'taux_abandon'   => $tauxAbandon,
            'par_niveau'     => $parNiveau,
        ];
    }

    /**
     * KPI frais supplémentaires (examen, activité)
     */
    private function getFraisSupplementairesKpi(): array
    {
        $types = [
            'examen'  => 5,
            'activite'=> 6,
        ];

        $result = [];
        foreach ($types as $label => $typeId) {
            $totalPrevu = $this->inscriptionRepo->activeQuery()
                ->where('annee_id', $this->anneeId)
                ->sum('frais_examen');
            $totalPaye = $this->detailRepo->activeQuery()
                ->where('annee_id', $this->anneeId)
                ->where('type_paiement', $typeId)
                ->where('statut_paiement', 1)
                ->sum('montant');
            $result[$label] = [
                'prevu'  => $totalPrevu,
                'paye'   => $totalPaye,
                'reste'  => $totalPrevu - $totalPaye,
                'taux'   => $totalPrevu > 0 ? round(($totalPaye / $totalPrevu) * 100, 2) : 0,
            ];
        }
        return $result;
    }

    private function getModeLabel(?int $mode): string
    {
        return match($mode) {
            1 => 'Espèces',
            2 => 'Chèque',
            3 => 'Carte bancaire',
            4 => 'Mobile money',
            default => 'Autre',
        };
    }
}
