<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\StockBoutiqueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class StockBoutiqueController extends Controller
{
    protected StockBoutiqueService $service;

    public function __construct(StockBoutiqueService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale – gestion du stock des boutiques
     */
    public function index(): View
    {
        return view('admin.stock-boutique.index', [
            'page_title' => 'Stock des boutiques'
        ]);
    }

    /**
     * API : Liste paginée du stock
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $request->only([
            'magasin_id', 'produit_id', 'search', 'stock_bas', 'rupture', 'per_page'
        ]);
        $result = $this->service->listStock($filters);
        return response()->json($result);
    }

    /**
     * API : Détail d’un produit dans une boutique
     */
    public function show(Request $request, int $produitId, int $magasinId): JsonResponse
    {
        try {
            $data = $this->service->getStockDetail($produitId, $magasinId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * API : Ajustement d’inventaire
     */
    public function ajuster(Request $request): JsonResponse
    {
        $request->validate([
            'produit_id'     => 'required|exists:produits,id',
            'magasin_id'     => 'required|exists:magasins,id',
            'quantite_reelle'=> 'required|numeric|min:0',
            'motif'          => 'required|string|max:255',
        ]);

        try {
            $this->service->ajusterInventaire(
                $request->produit_id,
                $request->magasin_id,
                $request->quantite_reelle,
                $request->motif
            );
            return response()->json(['success' => true, 'message' => 'Inventaire mis à jour']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : Alertes stock bas
     */
    public function alertes(): JsonResponse
    {
        $alertes = $this->service->alertesStockBas();
        return response()->json($alertes);
    }

    /**
     * API : Rapport d’inventaire pour une boutique
     */
    public function rapport(int $magasinId): JsonResponse
    {
        try {
            $rapport = $this->service->rapportInventaire($magasinId);
            return response()->json(['success' => true, 'data' => $rapport]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * API : Mouvements de stock sur une période
     */
    public function mouvements(Request $request): JsonResponse
    {
        $filters = $request->only([
            'magasin_id', 'produit_id', 'type', 'date_debut', 'date_fin', 'per_page'
        ]);
        $result = $this->service->mouvementsPeriode($filters);
        return response()->json($result);
    }
}
