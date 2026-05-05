<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class StockController extends Controller
{
    protected StockService $service;

    public function __construct(StockService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale : liste des produits et stock
     */
    public function index(): View
    {
        return view('admin.stock.index', ['page_title' => 'Gestion des stocks']);
    }

    /**
     * API : liste des produits (stock)
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'type_produit', 'stock_bas', 'annee_id', 'per_page']);
        $result = $this->service->listStock($filters);
        return response()->json(['success' => true, 'data' => $result['data'], 'aggregates' => $result['aggregates'], 'meta' => $result['pagination']]);
    }

    /**
     * API : détail produit + historique mouvements
     */
    public function show(int $produitId): JsonResponse
    {
        try {
            $detail = $this->service->getStockDetail($produitId);
            return response()->json(['success' => true, 'data' => $detail]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }
    }

    /**
     * API : statistiques dashboard
     */
    public function stats(): JsonResponse
    {
        $stats = $this->service->getStats();
        return response()->json(['success' => true, 'data' => $stats]);
    }

    /**
     * API : mouvements sur période
     */
    public function mouvements(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'produit_id', 'per_page']);
        $result = $this->service->getMouvementsPeriode($request->date_debut, $request->date_fin, $filters);
        return response()->json(['success' => true, 'data' => $result['data'], 'meta' => $result['pagination']]);
    }
}
