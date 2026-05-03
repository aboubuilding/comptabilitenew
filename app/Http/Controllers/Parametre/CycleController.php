<?php

namespace App\Http\Controllers\Admin\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCycleRequest;
use App\Http\Requests\UpdateCycleRequest;
use App\Services\CycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CycleController extends Controller
{
    protected CycleService $service;

    public function __construct(CycleService $service)
    {
        $this->service = $service;
    }

    /**
     * Affiche la vue avec tous les cycles (chargés depuis le service)
     */
    public function index(): View
    {
        $cycles = $this->service->getAllFormatted();

        return view('admin.cycles.index', [
            'cycles'     => $cycles,
            'page_title' => 'Cycles scolaires',
        ]);
    }

    /**
     * API : Liste des cycles (JSON)
     */
    public function list(): JsonResponse
    {
        $cycles = $this->service->getAllFormatted();
        return response()->json(['success' => true, 'data' => $cycles]);
    }

    /**
     * API : Détail d'un cycle
     */
    public function show(int $id): JsonResponse
    {
        try {
            $cycle = $this->service->show($id);
            return response()->json(['success' => true, 'data' => $cycle]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Cycle non trouvé'], 404);
        }
    }

    /**
     * API : Création
     */
    public function store(StoreCycleRequest $request): JsonResponse
    {
        try {
            $cycle = $this->service->store($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cycle créé avec succès',
                'data'    => $cycle->only(['id', 'libelle'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Mise à jour
     */
    public function update(UpdateCycleRequest $request, int $id): JsonResponse
    {
        try {
            $this->service->update($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Cycle modifié']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Suppression
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if ($this->service->hasRelatedData($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce cycle est utilisé ailleurs (niveaux, classes...). Suppression impossible.'
                ], 409);
            }

            $this->service->destroy($id);
            return response()->json(['success' => true, 'message' => 'Cycle supprimé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}