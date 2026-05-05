<?php

namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProduitRequest;
use App\Http\Requests\UpdateProduitRequest;
use App\Http\Requests\AjusterStockRequest;
use App\Services\ProduitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProduitController extends Controller
{
    protected ProduitService $service;

    public function __construct(ProduitService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.produits.index', [
            'page_title' => 'Gestion des produits et stocks'
        ]);
    }

    /**
     * Liste paginée (API)
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'type_produit', 'stock_bas', 'rupture', 'etat', 'per_page']);
        $result = $this->service->listProduits($filters);
        return response()->json(['success' => true, 'data' => $result['data'], 'meta' => $result['pagination']]);
    }

    /**
     * Détail d'un produit
     */
    public function show(int $id): JsonResponse
    {
        try {
            $produit = $this->service->getProduit($id);
            return response()->json(['success' => true, 'data' => $produit]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }
    }

    /**
     * Création
     */
    public function store(StoreProduitRequest $request): JsonResponse
    {
        try {
            $produit = $this->service->createProduit($request->validated());
            return response()->json(['success' => true, 'message' => 'Produit ajouté', 'data' => $produit], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Modification
     */
    public function update(UpdateProduitRequest $request, int $id): JsonResponse
    {
        try {
            $produit = $this->service->updateProduit($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Produit mis à jour', 'data' => $produit]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Ajustement de stock
     */
    public function ajusterStock(AjusterStockRequest $request, int $id): JsonResponse
    {
        try {
            $produit = $this->service->ajusterStock($id, $request->quantite, $request->motif);
            return response()->json(['success' => true, 'message' => 'Stock ajusté', 'data' => $produit]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Suppression
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteProduit($id);
            return response()->json(['success' => true, 'message' => 'Produit supprimé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Liste pour selects (dropdown)
     */
    public function select(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getForSelect()]);
    }

    /**
     * Historique des mouvements
     */
    public function mouvements(Request $request, int $id): JsonResponse
    {
        $filters = $request->only(['type', 'date_debut', 'date_fin', 'per_page']);
        $result = $this->service->getMouvements($id, $filters);
        return response()->json(['success' => true, 'data' => $result['data'], 'meta' => $result['pagination']]);
    }
}
