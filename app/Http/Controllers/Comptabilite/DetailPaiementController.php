<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DetailPaiementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DetailPaiementController extends Controller
{
    protected DetailPaiementService $service;

    public function __construct(DetailPaiementService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale
     */
    public function index(): View
    {
        return view('admin.details.index', [
            'page_title' => 'Détails des paiements'
        ]);
    }

    /**
     * API : liste des détails avec filtres
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date_debut', 'date_fin', 'type_paiement', 'eleve_search',
            'niveau_id', 'classe_id', 'cycle_id', 'type_inscription',
            'statut_paiement', 'mode_paiement', 'per_page'
        ]);

        $result = $this->service->listDetails($filters);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'aggregates' => $result['aggregates'],
            'meta' => $result['pagination']
        ]);
    }
}