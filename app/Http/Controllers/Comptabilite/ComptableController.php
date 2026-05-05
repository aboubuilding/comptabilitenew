<?php

namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComptableRequest;
use App\Http\Requests\UpdateComptableRequest;
use App\Services\ComptableService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ComptableController extends Controller
{
    protected ComptableService $service;

    public function __construct(ComptableService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue liste des comptables
     */
    public function index(): View
    {
        return view('admin.comptables.index', [
            'page_title' => 'Gestion des comptables'
        ]);
    }

    /**
     * API : liste des comptables
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['etat', 'search', 'per_page']);
        $result = $this->service->listeComptables($filters);
        return response()->json(['success' => true, 'data' => $result['data'], 'meta' => $result['pagination']]);
    }

    /**
     * API : détail d'un comptable
     */
    public function show(int $id): JsonResponse
    {
        try {
            $comptable = $this->service->getComptable($id);
            return response()->json(['success' => true, 'data' => $comptable]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Comptable non trouvé'], 404);
        }
    }

    /**
     * API : création
     */
    public function store(StoreComptableRequest $request): JsonResponse
    {
        try {
            $comptable = $this->service->createComptable($request->validated());
            return response()->json(['success' => true, 'message' => 'Comptable ajouté', 'data' => $comptable], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : modification
     */
    public function update(UpdateComptableRequest $request, int $id): JsonResponse
    {
        try {
            $comptable = $this->service->updateComptable($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Comptable mis à jour', 'data' => $comptable]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : suspension
     */
    public function suspendre(int $id): JsonResponse
    {
        try {
            $this->service->suspendre($id);
            return response()->json(['success' => true, 'message' => 'Comptable suspendu']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : réactivation
     */
    public function reactiver(int $id): JsonResponse
    {
        try {
            $this->service->reactiver($id);
            return response()->json(['success' => true, 'message' => 'Comptable réactivé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : suppression définitive
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteComptable($id);
            return response()->json(['success' => true, 'message' => 'Comptable supprimé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
