<?php

namespace App\Services;

use App\Repositories\Eloquent\InscriptionCantineRepository;
use App\Repositories\Eloquent\DetailRepository;
use App\Repositories\Eloquent\AchatRepository;
use App\Repositories\Eloquent\MouvementRepository;
use App\Repositories\Eloquent\PrevisionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyseCantineService
{
    protected ?int $anneeId;
    protected InscriptionCantineRepository $cantineRepo;
    protected DetailRepository $detailPaiementRepo;
    protected AchatRepository $achatRepo;
    protected MouvementRepository $recetteRepo;
    protected PrevisionRepository $previsionRepo;

    public function __construct(
        InscriptionCantineRepository $cantineRepo,
        DetailRepository $detailPaiementRepo,
        AchatRepository $achatRepo,
        MouvementRepository $recetteRepo,
        PrevisionRepository $previsionRepo
    ) {
        $this->cantineRepo = $cantineRepo;
        $this->detailPaiementRepo = $detailPaiementRepo;
        $this->achatRepo = $achatRepo;
        $this->recetteRepo = $recetteRepo;
        $this->previsionRepo = $previsionRepo;
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Indicateurs généraux de la cantine
     */
    public function getIndicateursGlobaux(): array
    {
        // Inscriptions actives
        $inscrits = $this->cantineRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->count();

        // Montants prévus, payés, restant
        $totalDu = $this->cantineRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->sum('montant_total_du');

        $totalPaye = $this->detailPaiementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_paiement', 2) // 2 = cantine
            ->where('statut_paiement', 1)
            ->sum('montant');

        $tauxRecouvrement = $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 2) : 0;

        // Dépenses cantine (achats de produits pour cantine)
        // type_achat = 1 pour cantine
        $totalDepenses = $this->achatRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_achat', 1)
            ->sum('montant_total');

        $solde = $totalPaye - $totalDepenses;

        return [
            'inscrits'          => $inscrits,
            'total_du'          => $totalDu,
            'total_paye'        => $totalPaye,
            'taux_recouvrement' => $tauxRecouvrement,
            'total_depenses'    => $totalDepenses,
            'solde'             => $solde,
        ];
    }

    /**
     * Évolution mensuelle des inscriptions, paiements, etc.
     */
    public function getEvolutionMensuelle(): array
    {
        $mois = collect(range(1, 12))->map(fn($m) => Carbon::create(null, $m, 1)->format('m-Y'));

        $inscriptions = $this->cantineRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->select(DB::raw('DATE_FORMAT(created_at, "%m-%Y") as mois'), DB::raw('count(*) as total'))
            ->groupBy('mois')
            ->get()
            ->keyBy('mois');

        $paiements = $this->detailPaiementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_paiement', 2)
            ->where('statut_paiement', 1)
            ->select(DB::raw('DATE_FORMAT(date_encaissement, "%m-%Y") as mois'), DB::raw('SUM(montant) as total'))
            ->groupBy('mois')
            ->get()
            ->keyBy('mois');

        $evolution = $mois->map(function ($m) use ($inscriptions, $paiements) {
            return [
                'mois'         => $m,
                'inscriptions' => $inscriptions[$m]->total ?? 0,
                'paiements'    => $paiements[$m]->total ?? 0,
            ];
        });

        return $evolution->values()->toArray();
    }

    /**
     * Performance par niveau (ou classe)
     */
    public function getPerformanceParNiveau(): array
    {
        $data = $this->cantineRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->with('inscription.niveau')
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
            });

        return $data->toArray();
    }

    /**
     * Rentabilité : recettes vs dépenses
     */
    public function getRentabilite(): array
    {
        $totalRecettes = $this->detailPaiementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_paiement', 2)
            ->where('statut_paiement', 1)
            ->sum('montant');

        $totalDepenses = $this->achatRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_achat', 1)
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
     * Coût moyen par repas (estimé)
     */
    public function getCoutMoyenParRepas(): array
    {
        $inscritsMoyens = $this->cantineRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->count();

        $moisActifs = 9;    // à ajuster selon calendrier scolaire
        $joursParMois = 20; // jours de présence moyenne
        $totalRepas = $inscritsMoyens * $moisActifs * $joursParMois;

        $depensesTotal = $this->achatRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_achat', 1)
            ->sum('montant_total');

        $coutMoyen = $totalRepas > 0 ? $depensesTotal / $totalRepas : 0;

        return [
            'total_repas_estimes'     => $totalRepas,
            'cout_moyen_par_repas'    => round($coutMoyen, 2),
        ];
    }

    /**
     * Top repas les plus consommés (via recettes et consommations)
     * Nécessite la relation `consommations` sur le modèle Recette.
     */
    public function getTopRepas(): array
    {
        $top = $this->recetteRepo->activeQuery()
            ->withCount('consommations')
            ->orderBy('consommations_count', 'desc')
            ->limit(5)
            ->get();

        return $top->map(fn($r) => [
            'libelle'    => $r->libelle,
            'fois_servi' => $r->consommations_count,
        ])->toArray();
    }

    /**
     * Prévisions vs réalisations (recettes cantine)
     */
    public function getPrevisionVsReel(): array
    {
        $prevu = $this->previsionRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type', 'recette')
            ->where('libelle', 'like', '%cantine%')
            ->sum('montant');

        $reel = $this->detailPaiementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_paiement', 2)
            ->where('statut_paiement', 1)
            ->sum('montant');

        return [
            'prevu'            => $prevu,
            'reel'             => $reel,
            'ecart'            => $reel - $prevu,
            'taux_realisation' => $prevu > 0 ? round(($reel / $prevu) * 100, 2) : 0,
        ];
    }
}
