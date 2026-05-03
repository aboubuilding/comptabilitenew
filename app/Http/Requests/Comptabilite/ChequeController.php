<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChequeStatutRequest;
use App\Services\ChequeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ChequeController extends Controller
{
    protected ChequeService $service;

    public function __construct(ChequeService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale – liste des chèques
     */
    public function index(): View
    {
        return view('admin.cheques.index', [
            'page_title' => 'Gestion des chèques'
        ]);
    }

    /**
     * API : liste des chèques avec filtres
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $request->only([
            'statut', 'banque_id', 'date_debut', 'date_fin', 'search', 'per_page'
        ]);
        $result = $this->service->listCheques($filters);
        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * API : mise à jour du statut d'un chèque
     */
    public function updateStatut(UpdateChequeStatutRequest $request, int $id): JsonResponse
    {
        try {
            $cheque = $this->service->updateStatut($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Statut du chèque mis à jour',
                'data'    => $cheque
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}