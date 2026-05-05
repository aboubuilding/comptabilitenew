<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAchatRequest;
use App\Http\Requests\UpdateAchatRequest;
use App\Services\AchatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AchatController extends Controller
{
    protected AchatService $service;

    public function __construct(AchatService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale (listes)
     */
    public function index(): View
    {
        return view('admin.achats.index', ['page_title' => 'Gestion des achats']);
    }

    /**
     * API : liste paginée des achats
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date_debut', 'date_fin', 'type_achat', 'fournisseur_id',
            'statut_paiement', 'per_page', 'annee_id'
        ]);
        $result = $this->service->listAchats($filters);
        return response()->json(['success' => true, 'data' => $result['data'], 'aggregates' => $result['aggregates'], 'meta' => $result['pagination']]);
    }

    /**
     * API : détail d'un achat
     */
    public function show(int $id): JsonResponse
    {
        try {
            $achat = $this->service->getAchat($id);
            return response()->json(['success' => true, 'data' => $achat]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Achat non trouvé'], 404);
        }
    }

    /**
     * API : créer un achat
     */
    public function store(StoreAchatRequest $request): JsonResponse
    {
        try {
            $achat = $this->service->createAchat($request->validated());
            return response()->json(['success' => true, 'message' => 'Achat enregistré avec succès', 'data' => $achat], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : mise à jour des statuts
     */
    public function update(UpdateAchatRequest $request, int $id): JsonResponse
    {
        try {
            $achat = $this->service->updateAchatStatus($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Achat mis à jour', 'data' => $achat]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : suppression (annulation)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteAchat($id);
            return response()->json(['success' => true, 'message' => 'Achat annulé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
