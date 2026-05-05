<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMagasinRequest;
use App\Http\Requests\UpdateMagasinRequest;
use App\Services\MagasinService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BoutiqueController extends Controller
{
    protected BoutiqueService $service;

    public function __construct(MagasinService $service)
    {
        $this->service = $service;
    }

    // Vue principale (liste des boutiques)
    public function index(): View
    {
        return view('admin.boutiques.index', ['page_title' => 'Boutiques']);
    }

    // API : liste paginée des boutiques
    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'per_page']);
        $result = $this->service->listBoutiques($filters);
        return response()->json(['success' => true, 'data' => $result['data'], 'meta' => $result['pagination']]);
    }

    // API : détail d’une boutique
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['date_debut', 'date_fin', 'produit_id', 'per_page']);
            $detail = $this->service->getBoutiqueDetail($id, $filters);
            return response()->json(['success' => true, 'data' => $detail]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
        }
    }

    // API : création
    public function store(StoreMagasinRequest $request): JsonResponse
    {
        try {
            $magasin = $this->service->createMagasin($request->validated());
            return response()->json(['success' => true, 'message' => 'Boutique ajoutée', 'data' => $magasin], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // API : modification
    public function update(UpdateMagasinRequest $request, int $id): JsonResponse
    {
        try {
            $magasin = $this->service->updateMagasin($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Boutique modifiée', 'data' => $magasin]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // API : suppression
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteMagasin($id);
            return response()->json(['success' => true, 'message' => 'Boutique supprimée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
