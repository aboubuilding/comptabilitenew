<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Repositories\InscriptionCantineRepository;
use App\Domain\ServicesEleves\Repositories\MenuRepository;
use App\Domain\ServicesEleves\Repositories\PreparationRepasRepository;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardCantineController extends Controller
{
    public function __construct(
        private InscriptionCantineRepository $inscriptions,
        private PreparationRepasRepository  $preparations,
        private MenuRepository               $menus,
    ) {
        $this->middleware('permission:cantine.voir');
    }

    public function index(Request $request)
    {
        $filters   = $request->only(['date_debut', 'date_fin']);
        $anneeId   = app(AnneeScolaireContext::class)->id();
        $dateDebut = $filters['date_debut'] ?? now()->startOfMonth()->toDateString();
        $dateFin   = $filters['date_fin']   ?? now()->toDateString();

        /* ================================================================
           1) STATS GLOBALES
           ================================================================ */
        $statsAbonnements = $this->inscriptions->compterActifs();

        $statsPreparations = $this->preparations->stats($anneeId, $dateDebut, $dateFin);

        // Taux d'occupation : parts servies / abonnements actifs
        $tauxOccupation = $statsAbonnements['total_actifs'] > 0
            ? round(($statsPreparations['parts'] / $statsAbonnements['total_actifs']) * 100, 1)
            : 0;

        /* ================================================================
           2) RENTABILITÉ : recettes vs coûts
           ================================================================ */
        // Recettes cantine encaissées sur la période
        $recettes = DB::table('details')
            ->where('etat', 1)
            ->whereNotNull('date_encaissement')
            ->whereDate('date_encaissement', '>=', $dateDebut)
            ->whereDate('date_encaissement', '<=', $dateFin)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->where(function ($q) {
                $q->whereNotNull('inscription_cantine_id')
                  ->orWhere('type_paiement', \App\Domain\Finances\Types\TypeDetail::CANTINE->value);
            })
            ->sum('montant');

        $coutTotal = $statsPreparations['cout_total'];

        $rentabilite = [
            'recettes'  => round((float) $recettes, 2),
            'couts'     => round((float) $coutTotal, 2),
            'marge'     => round((float) $recettes - (float) $coutTotal, 2),
            'marge_pct' => $recettes > 0
                ? round((((float) $recettes - (float) $coutTotal) / (float) $recettes) * 100, 1)
                : 0,
        ];

        /* ================================================================
           3) COÛT PAR JOUR (30 derniers jours)
           ================================================================ */
        $coutParJour = DB::table('preparations_repas')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->whereDate('date_preparation', '>=', now()->subDays(30))
            ->selectRaw('DATE(date_preparation) as jour, SUM(cout_reel) as cout, SUM(nombre_parts) as parts')
            ->groupBy('jour')
            ->orderBy('jour')
            ->get();

        /* ================================================================
           4) TOP MENUS (par nombre de préparations et coût)
           ================================================================ */
        $topMenus = DB::table('preparations_repas as p')
            ->join('menus as m', 'm.id', '=', 'p.menu_id')
            ->when($anneeId, fn ($q, $v) => $q->where('p.annee_id', $v))
            ->whereDate('p.date_preparation', '>=', $dateDebut)
            ->whereDate('p.date_preparation', '<=', $dateFin)
            ->groupBy('m.id', 'm.libelle')
            ->selectRaw('m.id, m.libelle,
                COUNT(*) as nb_preparations,
                SUM(p.nombre_parts) as parts,
                SUM(p.cout_reel) as cout_total')
            ->orderByDesc('parts')
            ->limit(10)
            ->get();

        /* ================================================================
           5) RÉPARTITION PAR TYPE DE REPAS
           ================================================================ */
        $parTypeRepas = DB::table('menus as m')
            ->join('preparations_repas as p', 'p.menu_id', '=', 'm.id')
            ->when($anneeId, fn ($q, $v) => $q->where('p.annee_id', $v))
            ->whereDate('p.date_preparation', '>=', $dateDebut)
            ->whereDate('p.date_preparation', '<=', $dateFin)
            ->groupBy('m.type_repas')
            ->selectRaw('m.type_repas, COUNT(*) as nb, SUM(p.nombre_parts) as parts, SUM(p.cout_reel) as cout')
            ->get()
            ->keyBy('type_repas');

        /* ================================================================
           6) ÉVOLUTION DES COÛTS (12 derniers mois)
           ================================================================ */
        $evolutionMensuelle = DB::table('preparations_repas')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->whereDate('date_preparation', '>=', now()->subMonths(12))
            ->selectRaw("
                DATE_FORMAT(date_preparation, '%Y-%m') as mois,
                SUM(cout_reel) as cout,
                SUM(nombre_parts) as parts
            ")
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        /* ================================================================
           7) STATS ABONNEMENTS
           ================================================================ */
        $statutsAbonnements = DB::table('inscriptions_cantine')
            ->where('etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->selectRaw('statut, COUNT(*) as nb, SUM(montant_mensuel) as mensuel')
            ->groupBy('statut')
            ->get()
            ->keyBy('statut');

        /* ================================================================
           8) CHART DATA
           ================================================================ */
        $chartData = [
            'cout_par_jour' => [
                'labels' => $coutParJour->pluck('jour')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))->all(),
                'values' => $coutParJour->pluck('cout')->map(fn ($v) => round((float) $v, 2))->all(),
                'parts'  => $coutParJour->pluck('parts')->map(fn ($v) => (int) $v)->all(),
            ],
            'top_menus' => [
                'labels' => $topMenus->pluck('libelle')->all(),
                'parts'  => $topMenus->pluck('parts')->map(fn ($v) => (int) $v)->all(),
                'couts'  => $topMenus->pluck('cout_total')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
            'rentabilite' => [
                'labels' => ['Recettes', 'Coûts'],
                'values' => [$rentabilite['recettes'], $rentabilite['couts']],
            ],
            'par_type_repas' => [
                'labels' => $parTypeRepas->map(fn ($t) => \App\Domain\ServicesEleves\Types\TypeRepas::tryFrom((int) $t->type_repas)?->label() ?? '—')->values()->all(),
                'parts'  => $parTypeRepas->pluck('parts')->map(fn ($v) => (int) $v)->values()->all(),
                'couts'  => $parTypeRepas->pluck('cout')->map(fn ($v) => round((float) $v, 2))->values()->all(),
            ],
            'evolution' => [
                'labels' => $evolutionMensuelle->pluck('mois')->all(),
                'couts'  => $evolutionMensuelle->pluck('cout')->map(fn ($v) => round((float) $v, 2))->all(),
                'parts'  => $evolutionMensuelle->pluck('parts')->map(fn ($v) => (int) $v)->all(),
            ],
        ];

        return view('domain.services-eleves.cantine.dashboard', [
            'statsAbonnements'   => $statsAbonnements,
            'statsPreparations'  => $statsPreparations,
            'tauxOccupation'     => $tauxOccupation,
            'rentabilite'        => $rentabilite,
            'topMenus'           => $topMenus,
            'parTypeRepas'       => $parTypeRepas,
            'statutsAbonnements' => $statutsAbonnements,
            'coutParJour'        => $coutParJour,
            'evolutionMensuelle' => $evolutionMensuelle,
            'chartData'          => $chartData,
            'filters'            => array_merge($filters, ['date_debut' => $dateDebut, 'date_fin' => $dateFin]),
        ]);
    }
}