<?php

namespace App\Http\Controllers\Admin\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFraisEcoleRequest;
use App\Http\Requests\UpdateFraisEcoleRequest;
use App\Services\FraisEcoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FraisEcoleController extends Controller
{
    protected FraisEcoleService $service;

    public function __construct(FraisEcoleService $service)
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
     * Affiche la vue (server‑side) avec les frais de l'année courante
     */
    public function index(): View
    {
        $anneeId = $this->getCurrentAnneeId();
        $frais = $anneeId ? $this->service->getAllFormatted($anneeId) : collect();

        return view('admin.frais.index', [
            'frais'      => $frais,
            'page_title' => 'Frais scolaires',
        ]);
    }

    /**
     * API : Liste JSON des frais de l'année courante
     */
    public function list(): JsonResponse
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return response()->json(['success' => false, 'message' => 'Aucune année scolaire en session.'], 400);
        }

        $frais = $this->service->getAllFormatted($anneeId);
        return response()->json(['success' => true, 'data' => $frais]);
    }

    /**
     * API : Liste JSON des frais par niveau (pour l'année courante)
     */
    public function listByNiveau(int $niveauId): JsonResponse
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return response()->json(['success' => false, 'message' => 'Aucune année scolaire en session.'], 400);
        }

        $frais = $this->service->getByNiveauAndAnnee($niveauId, $anneeId);
        return response()->json(['success' => true, 'data' => $frais]);
    }

    /**
     * API : Détail d'un frais
     */
    public function show(int $id): JsonResponse
    {
        try {
            $frais = $this->service->show($id);
            return response()->json(['success' => true, 'data' => $frais]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Frais non trouvé'], 404);
        }
    }

    /**
     * API : Création – l'année courante est ajoutée automatiquement
     */
    public function store(StoreFraisEcoleRequest $request): JsonResponse
    {
        try {
            $anneeId = $this->getCurrentAnneeId();
            if (!$anneeId) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire en session.'], 400);
            }

            $data = $request->validated();
            $data['annee_id'] = $anneeId;

            $frais = $this->service->store($data);
            return response()->json([
                'success' => true,
                'message' => 'Frais créé avec succès',
                'data'    => $frais->only(['id', 'libelle'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Mise à jour – l'année courante est ajoutée automatiquement
     */
    public function update(UpdateFraisEcoleRequest $request, int $id): JsonResponse
    {
        try {
            $anneeId = $this->getCurrentAnneeId();
            if (!$anneeId) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire en session.'], 400);
            }

            $data = $request->validated();
            $data['annee_id'] = $anneeId;

            $this->service->update($id, $data);
            return response()->json(['success' => true, 'message' => 'Frais modifié']);
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
                    'message' => 'Ce frais est utilisé dans des factures ou paiements. Suppression impossible.'
                ], 409);
            }

            $this->service->destroy($id);
            return response()->json(['success' => true, 'message' => 'Frais supprimé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}