<?php

namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActiviteRequest;
use App\Http\Requests\UpdateActiviteRequest;
use App\Services\ActiviteService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ActiviteController extends Controller
{
    protected ActiviteService $service;

    public function __construct(ActiviteService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale
     */
    public function index(): View
    {
        return view('admin.activites.index', [
            'page_title' => 'Activités extrascolaires'
        ]);
    }

    /**
     * API : liste avec statistiques
     */
    public function list(): JsonResponse
    {
        $activites = $this->service->getAllWithStats();
        return response()->json(['success' => true, 'data' => $activites]);
    }

    /**
     * API : détail
     */
    public function show(int $id): JsonResponse
    {
        try {
            $activite = $this->service->find($id);
            return response()->json(['success' => true, 'data' => $activite]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Activité non trouvée'], 404);
        }
    }

    /**
     * API : création
     */
    public function store(StoreActiviteRequest $request): JsonResponse
    {
        try {
            $activite = $this->service->store($request->validated());
            return response()->json(['success' => true, 'message' => 'Activité créée', 'data' => $activite], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : modification
     */
    public function update(UpdateActiviteRequest $request, int $id): JsonResponse
    {
        try {
            $activite = $this->service->update($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Activité modifiée', 'data' => $activite]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : suppression
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if ($this->service->hasRelatedPayments($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Des paiements sont associés à cette activité, suppression impossible.'
                ], 409);
            }
            $this->service->delete($id);
            return response()->json(['success' => true, 'message' => 'Activité supprimée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
