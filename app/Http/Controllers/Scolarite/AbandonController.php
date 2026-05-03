<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AbandonEleveRequest;
use App\Services\AbandonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AbandonController extends Controller
{
    protected AbandonService $service;

    public function __construct(AbandonService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue liste des abandons
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['cycle_id', 'niveau_id', 'classe_id', 'search', 'per_page']);
        $data = $this->service->listAbandons($filters);
        $stats = $this->service->getStats();

        return view('admin.abandons.index', [
            'abandons'   => $data['data'],
            'pagination' => $data['pagination'],
            'filters'    => $filters,
            'stats'      => $stats,
            'page_title' => 'Abandons scolaires',
        ]);
    }

    /**
     * API : enregistrer un abandon
     */
    public function store(AbandonEleveRequest $request, int $inscriptionId): JsonResponse
    {
        try {
            $userId = Auth::id();
            $abandon = $this->service->marquerAbandon($inscriptionId, $request->validated(), $userId);
            return response()->json([
                'success' => true,
                'message' => 'Abandon enregistré',
                'data'    => $abandon
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : annuler un abandon
     */
    public function destroy(int $inscriptionId): JsonResponse
    {
        try {
            $this->service->annulerAbandon($inscriptionId);
            return response()->json([
                'success' => true,
                'message' => 'Abandon annulé, l\'élève est réinscrit'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}