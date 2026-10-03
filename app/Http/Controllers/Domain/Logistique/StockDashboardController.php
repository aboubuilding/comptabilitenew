<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\Logistique\Repositories\ProduitRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockDashboardController extends Controller
{
    public function __construct(
        private ProduitRepository $produits,
        private MagasinRepository $magasins,
    ) {
        $this->middleware('permission:stock.voir');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['magasin_id', 'date_debut', 'date_fin']);

        // 1) Stats globales
        $stats = $this->produits->compterStatistiques();

        // 2) Produits en alerte (top 10, triés par écart seuil/quantité)
        $alertes = DB::table('stock_actuel as sa')
            ->join('produits as p', 'p.id', '=', 'sa.produit_id')
            ->join('magasins as m', 'm.id', '=', 'sa.magasin_id')
            ->where('sa.etat', 1)
            ->whereNotNull('sa.seuil_alerte')
            ->whereColumn('sa.quantite', '<=', 'sa.seuil_alerte')
            ->when($filters['magasin_id'] ?? null, fn ($q, $v) => $q->where('sa.magasin_id', $v))
            ->select([
                'p.id as produit_id', 'p.libelle as produit',
                'm.id as magasin_id', 'm.libelle as magasin',
                'sa.quantite', 'sa.seuil_alerte',
                DB::raw('(sa.seuil_alerte - sa.quantite) as deficit'),
            ])
            ->orderByDesc('deficit')
            ->limit(10)
            ->get();

        // 3) Valeur du stock par magasin
        $valeurParMagasin = DB::table('stock_actuel as sa')
            ->join('magasins as m', 'm.id', '=', 'sa.magasin_id')
            ->where('sa.etat', 1)
            ->groupBy('m.id', 'm.libelle', 'm.type')
            ->selectRaw('m.id, m.libelle, m.type,
                SUM(sa.valeur_stock) as valeur,
                SUM(sa.quantite) as quantite,
                COUNT(sa.id) as nb_produits')
            ->orderByDesc('valeur')
            ->get();

        // 4) Top 10 produits par valeur de stock
        $topProduits = DB::table('stock_actuel as sa')
            ->join('produits as p', 'p.id', '=', 'sa.produit_id')
            ->where('sa.etat', 1)
            ->when($filters['magasin_id'] ?? null, fn ($q, $v) => $q->where('sa.magasin_id', $v))
            ->groupBy('p.id', 'p.libelle')
            ->selectRaw('p.id, p.libelle,
                SUM(sa.valeur_stock) as valeur,
                SUM(sa.quantite) as quantite')
            ->orderByDesc('valeur')
            ->limit(10)
            ->get();

        // 5) Mouvements récents (7 derniers jours) par type
        $mouvementsRecents = DB::table('stocks')
            ->where('etat', 1)
            ->when($filters['magasin_id'] ?? null, fn ($q, $v) => $q->where('magasin_id', $v))
            ->whereDate('date_stock', '>=', now()->subDays(7))
            ->selectRaw('type_mouvement, COUNT(*) as nb, SUM(quantite) as quantite')
            ->groupBy('type_mouvement')
            ->get()
            ->keyBy('type_mouvement');

        // 6) Évolution de la valeur du stock par mois (12 derniers mois)
        $evolutionMensuelle = DB::table('stocks')
            ->where('etat', 1)
            ->whereDate('date_stock', '>=', now()->subMonths(12))
            ->when($filters['magasin_id'] ?? null, fn ($q, $v) => $q->where('magasin_id', $v))
            ->selectRaw("
                DATE_FORMAT(date_stock, '%Y-%m') as mois,
                SUM(CASE WHEN type_mouvement IN (1,4) THEN valeur_mouvement ELSE 0 END) as valeur_entrees,
                SUM(CASE WHEN type_mouvement IN (2,3,5) THEN valeur_mouvement ELSE 0 END) as valeur_sorties
            ")
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // 7) Liste des magasins (pour le filtre)
        $magasins = $this->magasins->listePourSelect();

        // 8) Préparation des datasets Chart.js (côté serveur)
        $chartData = [
            'valeur_par_magasin' => [
                'labels' => $valeurParMagasin->pluck('libelle')->all(),
                'values' => $valeurParMagasin->pluck('valeur')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
            'top_produits' => [
                'labels' => $topProduits->pluck('libelle')->all(),
                'values' => $topProduits->pluck('valeur')->map(fn ($v) => round((float) $v, 2))->all(),
                'quantites' => $topProduits->pluck('quantite')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
            'evolution' => [
                'labels'   => $evolutionMensuelle->pluck('mois')->all(),
                'entrees'  => $evolutionMensuelle->pluck('valeur_entrees')->map(fn ($v) => round((float) $v, 2))->all(),
                'sorties'  => $evolutionMensuelle->pluck('sorties')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
        ];

        return view('domain.logistique.stock.dashboard', [
            'stats'              => $stats,
            'alertes'            => $alertes,
            'valeurParMagasin'   => $valeurParMagasin,
            'topProduits'        => $topProduits,
            'mouvementsRecents'  => $mouvementsRecents,
            'evolutionMensuelle' => $evolutionMensuelle,
            'magasins'           => $magasins,
            'filters'            => $filters,
            'chartData'          => $chartData,
        ]);
    }
}