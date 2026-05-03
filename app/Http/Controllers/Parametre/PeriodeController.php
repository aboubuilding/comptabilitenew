<?php

namespace App\Http\Controllers\Admin\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodeRequest;
use App\Http\Requests\UpdatePeriodeRequest;
use App\Services\PeriodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PeriodeController extends Controller
{
    protected PeriodeService $service;

    public function __construct(PeriodeService $service)
    {
        $this->service = $service;
    }

    /**
     * Récupère l'ID de l'année en cours depuis la session
     */
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Affiche la vue avec les périodes de l'année courante
     */
    public function index(): View
    {
        $anneeId = $this->getCurrentAnneeId();
        $periodes = $anneeId ? $this->service->getAllFormatted($anneeId) : collect();

        return view('admin.periodes.index', [
            'periodes'    => $periodes,
            'annee_id'    => $anneeId,
            'page_title'  => 'Périodes scolaires',
        ]);
    }

    /**
     * API : Liste des périodes pour l'année courante (JSON)
     */
    public function list(): JsonResponse
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return response()->json(['success' => false, 'message' => 'Aucune année scolaire en session.'], 400);
        }

        $periodes = $this->service->getAllFormatted($anneeId);
        return response()->json(['success' => true, 'data' => $periodes]);
    }

    /**
     * API : Détail d'une période (JSON)
     */
    public function show(int $id): JsonResponse
    {
        try {
            $periode = $this->service->show($id);
            return response()->json(['success' => true, 'data' => $periode]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Période non trouvée'], 404);
        }
    }

    /**
     * API : Création
     */
    public function store(StorePeriodeRequest $request): JsonResponse
    {
        try {
            $periode = $this->service->store($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Période créée avec succès',
                'data'    => $periode->only(['id', 'libelle'])
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
    public function update(UpdatePeriodeRequest $request, int $id): JsonResponse
    {
        try {
            $this->service->update($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Période modifiée']);
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
                    'message' => 'Cette période est utilisée ailleurs (notes, évaluations...). Suppression impossible.'
                ], 409);
            }

            $this->service->destroy($id);
            return response()->json(['success' => true, 'message' => 'Période supprimée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}