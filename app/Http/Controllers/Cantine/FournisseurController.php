<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFournisseurRequest;
use App\Http\Requests\UpdateFournisseurRequest;
use App\Services\FournisseurService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FournisseurController extends Controller
{
    protected FournisseurService $service;

    public function __construct(FournisseurService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale
     */
    public function index(): View
    {
        return view('admin.fournisseurs.index', [
            'page_title' => 'Gestion des fournisseurs'
        ]);
    }

    /**
     * API : liste des fournisseurs
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'etat', 'per_page']);
        $result = $this->service->listFournisseurs($filters);
        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['pagination']
        ]);
    }

    /**
     * API : détail d'un fournisseur
     */
    public function show(int $id): JsonResponse
    {
        try {
            $fournisseur = $this->service->getFournisseur($id);
            return response()->json(['success' => true, 'data' => $fournisseur]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Fournisseur non trouvé'], 404);
        }
    }

    /**
     * API : création
     */
    public function store(StoreFournisseurRequest $request): JsonResponse
    {
        try {
            $fournisseur = $this->service->createFournisseur($request->validated());
            return response()->json(['success' => true, 'message' => 'Fournisseur ajouté', 'data' => $fournisseur], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : mise à jour
     */
    public function update(UpdateFournisseurRequest $request, int $id): JsonResponse
    {
        try {
            $fournisseur = $this->service->updateFournisseur($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Fournisseur mis à jour', 'data' => $fournisseur]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : suppression (désactivation)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteFournisseur($id);
            return response()->json(['success' => true, 'message' => 'Fournisseur désactivé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : liste pour select (dropdown)
     */
    public function select(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getForSelect()]);
    }

    /**
     * API : statistiques des achats par fournisseur
     */
    public function stats(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin'   => 'nullable|date|after_or_equal:date_debut',
        ]);
        $stats = $this->service->getStatsAchats($id, $validated['date_debut'] ?? null, $validated['date_fin'] ?? null);
        return response()->json(['success' => true, 'data' => $stats]);
    }
}
