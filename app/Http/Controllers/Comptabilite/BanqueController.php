<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBanqueRequest;
use App\Http\Requests\UpdateBanqueRequest;
use App\Services\BanqueService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BanqueController extends Controller
{
    protected BanqueService $service;

    public function __construct(BanqueService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale – liste des banques
     */
    public function index(): View
    {
        return view('admin.banques.index', [
            'page_title' => 'Banques'
        ]);
    }

    /**
     * API : Liste des banques avec statistiques
     */
    public function list(): JsonResponse
    {
        $banques = $this->service->getAllWithStats();
        return response()->json(['success' => true, 'data' => $banques]);
    }

    /**
     * API : Détail d'une banque
     */
    public function show(int $id): JsonResponse
    {
        try {
            $banque = $this->service->find($id);
            return response()->json(['success' => true, 'data' => $banque]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Banque non trouvée'], 404);
        }
    }

    /**
     * API : Création
     */
    public function store(StoreBanqueRequest $request): JsonResponse
    {
        try {
            $banque = $this->service->store($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Banque créée avec succès',
                'data'    => $banque
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : Mise à jour
     */
    public function update(UpdateBanqueRequest $request, int $id): JsonResponse
    {
        try {
            $banque = $this->service->update($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Banque modifiée',
                'data'    => $banque
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API : Suppression
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if ($this->service->hasRelatedCheques($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette banque est liée à des chèques. Suppression impossible.'
                ], 409);
            }
            $this->service->delete($id);
            return response()->json(['success' => true, 'message' => 'Banque supprimée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}