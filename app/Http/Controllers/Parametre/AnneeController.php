<?php

namespace App\Http\Controllers\Admin\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnneeRequest;
use App\Http\Requests\UpdateAnneeRequest;
use App\Services\AnneeService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AnneeController extends Controller
{
    protected AnneeService $service;

    public function __construct(AnneeService $service)
    {
        $this->service = $service;
    }

    /**
     * Affiche la vue avec les années chargées depuis le service
     */
    public function index(): View
    {
        // ✅ On charge les données depuis le service
        $annees = $this->service->getAllFormatted();

        return view('admin.annees.index', [
            'annees'     => $annees,
            'page_title' => 'Années scolaires',
        ]);
    }

    /**
     * API : Détail d'une année (JSON)
     */
    public function show(int $id): JsonResponse
    {
        try {
            $annee = $this->service->show($id);
            if (!$annee) {
                return response()->json(['success' => false, 'message' => 'Année non trouvée'], 404);
            }
            return response()->json(['success' => true, 'data' => $annee]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Création (JSON)
     */
    public function store(StoreAnneeRequest $request): JsonResponse
    {
        try {
            $annee = $this->service->store($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Année créée avec succès',
                'data'    => $annee->only(['id', 'libelle'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Mise à jour (JSON)
     */
    public function update(UpdateAnneeRequest $request, int $id): JsonResponse
    {
        try {
            $this->service->update($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Année modifiée'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Suppression (JSON)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if ($this->service->hasRelatedData($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette année a des données associées (inscriptions, frais...). Suppression impossible.'
                ], 409);
            }

            $this->service->destroy($id);
            return response()->json([
                'success' => true,
                'message' => 'Année supprimée'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}