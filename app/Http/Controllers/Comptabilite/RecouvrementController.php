<?php

namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\RecouvrementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RecouvrementController extends Controller
{
    protected RecouvrementService $service;

    public function __construct(RecouvrementService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale avec choix du type de frais
     */
    public function index(): View
    {
        return view('admin.recouvrement.index', [
            'page_title' => 'Recouvrement des frais'
        ]);
    }

    /**
     * API : liste des élèves en impayé pour un type donné
     */
    public function listImpayes(Request $request, int $typeFrais): JsonResponse
    {
        try {
            $filters = $request->only(['cycle_id', 'niveau_id', 'classe_id', 'search', 'per_page']);
            $result = $this->service->getElevesImpayes($typeFrais, $filters);
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'aggregates' => $result['aggregates'],
                'meta' => $result['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
