<?php

namespace App\Http\Controllers\Admin\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNiveauRequest;
use App\Http\Requests\UpdateNiveauRequest;
use App\Services\NiveauService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NiveauController extends Controller
{
    protected NiveauService $service;

    public function __construct(NiveauService $service)
    {
        $this->service = $service;
    }

    /**
     * Affiche la vue avec tous les niveaux (chargés depuis le service)
     */
    public function index(): View
    {
        $niveaux = $this->service->getAllFormatted();

        return view('admin.niveaux.index', [
            'niveaux'    => $niveaux,
            'page_title' => 'Niveaux scolaires',
        ]);
    }

    /**
     * API : Liste des niveaux (JSON)
     */
    public function list(): JsonResponse
    {
        $niveaux = $this->service->getAllFormatted();
        return response()->json(['success' => true, 'data' => $niveaux]);
    }

    /**
     * API : Liste des niveaux pour un cycle (optionnel en paramètre)
     */
    public function listByCycle(int $cycleId): JsonResponse
    {
        $niveaux = $this->service->getByCycle($cycleId);
        return response()->json(['success' => true, 'data' => $niveaux]);
    }

    /**
     * API : Détail d'un niveau
     */
    public function show(int $id): JsonResponse
    {
        try {
            $niveau = $this->service->show($id);
            return response()->json(['success' => true, 'data' => $niveau]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Niveau non trouvé'], 404);
        }
    }

    /**
     * API : Création
     */
    public function store(StoreNiveauRequest $request): JsonResponse
    {
        try {
            $niveau = $this->service->store($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Niveau créé avec succès',
                'data'    => $niveau->only(['id', 'libelle'])
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
    public function update(UpdateNiveauRequest $request, int $id): JsonResponse
    {
        try {
            $this->service->update($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Niveau modifié']);
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
                    'message' => 'Ce niveau est utilisé ailleurs (classes, inscriptions...). Suppression impossible.'
                ], 409);
            }

            $this->service->destroy($id);
            return response()->json(['success' => true, 'message' => 'Niveau supprimé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}