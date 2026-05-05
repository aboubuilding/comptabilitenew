<?php
namespace App\Services;

use App\Models\InscriptionCantine;
use App\Models\DetailPaiement;
use App\Models\Recette;
use App\Models\AchatDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyseCantineService
{
    protected $anneeId;

    public function __construct()
    {
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Indicateurs généraux de la cantine
     */
    public function getIndicateursGlobaux(): array
    {
        // Inscriptions actives
        $inscrits = InscriptionCantine::where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->count();

        // Montants prévus, payés, restant
        $totalDu = InscriptionCantine::where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->sum('montant_total_du');

        $totalPaye = DetailPaiement::where('annee_id', $this->anneeId)
            ->where('type_paiement', 2) // 2 = cantine
            ->where('statut_paiement', 1)
            ->sum('montant');

        $tauxRecouvrement = $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 2) : 0;

        // Dépenses cantine (achats de produits pour cantine)
        // On suppose qu'il y a un champ `type_achat` = 1 pour cantine dans la table `achats`
        $totalDepenses = DB::table('achats')
            ->where('annee_id', $this->anneeId)
            ->where('type_achat', 1) // 1 = cantine
            ->where('etat', 1)
            ->sum('montant_total');

        // Solde (marge) = encaissements - dépenses
        $solde = $totalPaye - $totalDepenses;

        return [
            'inscrits'           => $inscrits,
            'total_du'           => $totalDu,
            'total_paye'         => $totalPaye,
            'taux_recouvrement'  => $tauxRecouvrement,
            'total_depenses'     => $totalDepenses,
            'solde'              => $solde,
        ];
    }

    /**
     * Évolution mensuelle des inscriptions, paiements, etc.
     */
    public function getEvolutionMensuelle(): array
    {
        // On prend les 12 derniers mois (ou depuis début année scolaire)
        $mois = collect(range(1, 12))->map(fn($m) => Carbon::create(null, $m, 1)->format('m-Y'));

        $inscriptions = InscriptionCantine::where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->select(DB::raw('DATE_FORMAT(created_at, "%m-%Y") as mois'), DB::raw('count(*) as total'))
            ->groupBy('mois')
            ->get()
            ->keyBy('mois');

        $paiements = DetailPaiement::where('annee_id', $this->anneeId)
            ->where('type_paiement', 2)
            ->where('statut_paiement', 1)
            ->select(DB::raw('DATE_FORMAT(date_encaissement, "%m-%Y") as mois'), DB::raw('SUM(montant) as total'))
            ->groupBy('mois')
            ->get()
            ->keyBy('mois');

        $evolution = $mois->map(function ($m) use ($inscriptions, $paiements) {
            return [
                'mois'          => $m,
                'inscriptions'  => $inscriptions[$m]->total ?? 0,
                'paiements'     => $paiements[$m]->total ?? 0,
            ];
        });

        return $evolution->values()->toArray();
    }

    /**
     * Performance par niveau (ou classe) – montant prévu vs payé
     */
    public function getPerformanceParNiveau(): array
    {
        return InscriptionCantine::with('inscription.niveau')
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->get()
            ->groupBy(fn($i) => $i->inscription->niveau?->libelle ?? 'N/C')
            ->map(function ($group) {
                $totalDu = $group->sum('montant_total_du');
                $totalPaye = $group->sum(fn($i) => $i->montant_paye);
                return [
                    'total_du'   => $totalDu,
                    'total_paye' => $totalPaye,
                    'reste'      => $totalDu - $totalPaye,
                    'taux'       => $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Taux de couverture des dépenses par les recettes
     * (Rentabilité de la cantine)
     */
    public function getRentabilite(): array
    {
        $totalRecettes = DetailPaiement::where('annee_id', $this->anneeId)
            ->where('type_paiement', 2)
            ->where('statut_paiement', 1)
            ->sum('montant');

        $totalDepenses = DB::table('achats')
            ->where('annee_id', $this->anneeId)
            ->where('type_achat', 1)
            ->where('etat', 1)
            ->sum('montant_total');

        $marge = $totalRecettes - $totalDepenses;
        $tauxCouverture = $totalDepenses > 0 ? round(($totalRecettes / $totalDepenses) * 100, 2) : 0;

        return [
            'recettes'        => $totalRecettes,
            'depenses'        => $totalDepenses,
            'marge'           => $marge,
            'taux_couverture' => $tauxCouverture,
        ];
    }

    /**
     * Coût moyen par repas (si vous avez le nombre de repas servis)
     * Si vous n’avez pas de comptage des repas, vous pouvez estimer à partir du nombre d’inscrits * jours de présence.
     */
    public function getCoutMoyenParRepas(): array
    {
        // Nombre total de repas servis (à adapter selon votre logique – exemple : 20 jours/mois * nombre_inscrits)
        $inscritsMoyens = InscriptionCantine::where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->count();

        $moisActifs = 9; // à ajuster
        $joursParMois = 20;
        $totalRepas = $inscritsMoyens * $moisActifs * $joursParMois;

        $depensesTotal = DB::table('achats')
            ->where('annee_id', $this->anneeId)
            ->where('type_achat', 1)
            ->where('etat', 1)
            ->sum('montant_total');

        $coutMoyen = $totalRepas > 0 ? $depensesTotal / $totalRepas : 0;

        return [
            'total_repas_estimes' => $totalRepas,
            'cout_moyen_par_repas'=> round($coutMoyen, 2),
        ];
    }

    /**
     * Liste des repas (menus) les plus utilisés (consommés)
     * Nécessite une table `consommations_repas` ou bien vous pouvez compter depuis `recettes`.
     */
    public function getTopRepas(): array
    {
        // Exemple : si vous avez une table `consommations` avec `recette_id` et `quantite`
        // Ici on retourne un échantillon
        return Recette::withCount('consommations')
            ->orderBy('consommations_count', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'libelle' => $r->libelle,
                'fois_servi' => $r->consommations_count,
            ])
            ->toArray();
    }

    /**
     * Prévisions vs réalisations (pour la cantine uniquement)
     */
    public function getPrevisionVsReel(): array
    {
        // Prévisions de recettes cantine (type=cantine dans previsions ? ou libelle contient 'cantine')
        $prevu = DB::table('previsions')
            ->where('annee_id', $this->anneeId)
            ->where('type', 'recette')
            ->where('libelle', 'like', '%cantine%')
            ->sum('montant');

        $reel = DetailPaiement::where('annee_id', $this->anneeId)
            ->where('type_paiement', 2)
            ->where('statut_paiement', 1)
            ->sum('montant');

        return [
            'prevu' => $prevu,
            'reel'  => $reel,
            'ecart' => $reel - $prevu,
            'taux_realisation' => $prevu > 0 ? round(($reel / $prevu) * 100, 2) : 0,
        ];
    }
}