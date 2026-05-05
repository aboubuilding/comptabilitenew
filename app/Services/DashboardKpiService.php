<?php
namespace App\Services;

use App\Models\Inscription;
use App\Models\DetailPaiement;
use App\Models\Vente;
use App\Models\Depense;
use App\Models\AbonnementBus;
use App\Models\InscriptionCantine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardKpiService
{
    protected $anneeId;

    public function __construct()
    {
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
        $query = Inscription::where('annee_id', $this->anneeId)->where('etat', 1);

        $total = $query->count();
        $garcons = (clone $query)->whereHas('eleve', fn($q) => $q->where('sexe', 1))->count();
        $filles = (clone $query)->whereHas('eleve', fn($q) => $q->where('sexe', 0))->count();

        // Évolution mensuelle (les 12 derniers mois)
        $evolution = Inscription::where('annee_id', $this->anneeId)
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mois'), DB::raw('count(*) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // Répartition par niveau
        $parNiveau = Inscription::with('niveau')
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
        // Montant total prévu (frais_scolarite + frais_inscription + etc.)
        $totalPrevu = Inscription::where('annee_id', $this->anneeId)
            ->sum(DB::raw('COALESCE(frais_scolarite,0) + COALESCE(frais_inscription,0) + COALESCE(frais_assurance,0)'));

        // Montant total encaissé (type_paiement=1 scolarité, statut_paiement=1 encaissé)
        $totalEncaissé = DetailPaiement::where('annee_id', $this->anneeId)
            ->where('type_paiement', 1)
            ->where('statut_paiement', 1)
            ->sum('montant');

        $tauxRecouvrement = $totalPrevu > 0 ? round(($totalEncaissé / $totalPrevu) * 100, 2) : 0;

        // Impayés (reste à payer)
        $impayes = $totalPrevu - $totalEncaissé;

        return [
            'total_prevu'        => $totalPrevu,
            'total_encaisse'     => $totalEncaissé,
            'taux_recouvrement'  => $tauxRecouvrement,
            'impayes'            => max($impayes, 0),
        ];
    }

    /**
     * KPI paiements (tous types)
     */
    private function getPaiementsKpi(): array
    {
        // Répartition par mode de paiement
        $parMode = DetailPaiement::join('paiements', 'details.paiement_id', '=', 'paiements.id')
            ->where('details.annee_id', $this->anneeId)
            ->where('details.statut_paiement', 1)
            ->select('paiements.mode_paiement', DB::raw('SUM(details.montant) as total'))
            ->groupBy('paiements.mode_paiement')
            ->get()
            ->map(fn($p) => ['mode' => $this->getModeLabel($p->mode_paiement), 'total' => $p->total]);

        // Évolution mensuelle des encaissements
        $evolution = DetailPaiement::where('annee_id', $this->anneeId)
            ->where('statut_paiement', 1)
            ->select(DB::raw('DATE_FORMAT(date_encaissement, "%Y-%m") as mois'), DB::raw('SUM(montant) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // Paiements en attente (non encaissés)
        $enAttente = DetailPaiement::where('annee_id', $this->anneeId)
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
        $inscrits = InscriptionCantine::where('annee_id', $this->anneeId)->where('statut', 1)->count();
        $totalDu = InscriptionCantine::where('annee_id', $this->anneeId)->where('statut', 1)->sum('montant_total_du');
        $totalPaye = InscriptionCantine::where('annee_id', $this->anneeId)
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
        $inscrits = AbonnementBus::where('annee_id', $this->anneeId)->where('statut', 1)->count();
        $totalDu = AbonnementBus::where('annee_id', $this->anneeId)->where('statut', 1)->sum('montant_total_du');
        $totalPaye = AbonnementBus::where('annee_id', $this->anneeId)
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
        $totalCA = Vente::where('annee_id', $this->anneeId)->where('etat', 1)->sum('montant_total');
        $evolution = Vente::where('annee_id', $this->anneeId)
            ->select(DB::raw('DATE_FORMAT(date_vente, "%Y-%m") as mois'), DB::raw('SUM(montant_total) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $topProduits = Vente::with('produit')
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
        $totalDepenses = Depense::where('annee_id', $this->anneeId)->where('etat', 1)->sum('montant');
        $parMotif = Depense::where('annee_id', $this->anneeId)
            ->select('motif', DB::raw('SUM(montant) as total'))
            ->groupBy('motif')
            ->get();

        $evolution = Depense::where('annee_id', $this->anneeId)
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
        $totalAbandons = Inscription::where('annee_id', $this->anneeId)
            ->where('statut_abandon', 1)
            ->count();

        $parNiveau = Inscription::where('annee_id', $this->anneeId)
            ->where('statut_abandon', 1)
            ->with('niveau')
            ->select('niveau_id', DB::raw('count(*) as total'))
            ->groupBy('niveau_id')
            ->get()
            ->map(fn($i) => ['niveau' => $i->niveau?->libelle, 'total' => $i->total]);

        $tauxAbandon = Inscription::where('annee_id', $this->anneeId)->count() > 0
            ? round(($totalAbandons / Inscription::where('annee_id', $this->anneeId)->count()) * 100, 2)
            : 0;

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
            $totalPrevu = Inscription::where('annee_id', $this->anneeId)->sum('frais_examen');
            $totalPaye = DetailPaiement::where('annee_id', $this->anneeId)
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