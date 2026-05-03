<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaiementRequest;
use App\Http\Requests\UpdatePaiementRequest;
use App\Services\PaiementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PaiementController extends Controller
{
    protected PaiementService $service;

    public function __construct(PaiementService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.paiements.index', [
            'page_title' => 'Paiements'
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['statut_paiement', 'mode_paiement', 'inscription_id', 'search', 'per_page']);
        $result = $this->service->listPaiements($filters);
        return response()->json($result);
    }

    public function listEnAttente(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'per_page']);
        $result = $this->service->listPaiementsEnAttente($filters);
        return response()->json($result);
    }

    public function store(StorePaiementRequest $request): JsonResponse
    {
        try {
            $paiement = $this->service->store($request->validated(), auth()->id());
            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistré avec succès',
                'data'    => $paiement
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function valider(int $id): JsonResponse
    {
        try {
            $paiement = $this->service->validerPaiement($id, auth()->id());
            return response()->json([
                'success' => true,
                'message' => 'Paiement encaissé',
                'data'    => $paiement
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->validate(['motif' => 'required|string']);
        try {
            $paiement = $this->service->annulerPaiement($id, $request->motif);
            return response()->json([
                'success' => true,
                'message' => 'Paiement annulé',
                'data'    => $paiement
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getTotaux(int $inscriptionId): JsonResponse
    {
        try {
            $totaux = $this->service->getTotauxParInscription($inscriptionId);
            return response()->json(['success' => true, 'data' => $totaux]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }
}