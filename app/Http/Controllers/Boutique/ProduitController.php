<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProduitRequest;
use App\Http\Requests\UpdateProduitRequest;
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
        return view('admin.produits.index', ['page_title' => 'Gestion des produits']);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'categorie', 'type_produit', 'stock_bas', 'per_page']);
        $result = $this->service->listProduits($filters);
        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $produit = $this->service->getProduit($id);
            return response()->json(['success' => true, 'data' => $produit]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }
    }

    public function store(StoreProduitRequest $request): JsonResponse
    {
        try {
            $produit = $this->service->createProduit($request->validated());
            return response()->json(['success' => true, 'message' => 'Produit créé', 'data' => $produit], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateProduitRequest $request, int $id): JsonResponse
    {
        try {
            $produit = $this->service->updateProduit($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Produit mis à jour', 'data' => $produit]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteProduit($id);
            return response()->json(['success' => true, 'message' => 'Produit désactivé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function select(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getForSelect()]);
    }
}
