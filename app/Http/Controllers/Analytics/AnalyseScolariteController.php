<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\AnalyseScolariteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AnalyseScolariteController extends Controller
{
    protected $service;

    public function __construct(AnalyseScolariteService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.analyse.scolarite', [
            'page_title' => 'Analyse des paiements de scolarité'
        ]);
    }

    public function global(): JsonResponse
    {
        return response()->json($this->service->getRecapitulatifGlobal());
    }

    public function parNiveau(): JsonResponse
    {
        return response()->json($this->service->getAnalyseParNiveau());
    }

    public function impayes(Request $request): JsonResponse
    {
        $filters = $request->only(['classe_id', 'niveau_id', 'cycle_id', 'search', 'per_page']);
        $result = $this->service->getElevesImpayes($filters);
        return response()->json($result);
    }

    public function evolution(): JsonResponse
    {
        return response()->json($this->service->getEvolutionMensuelle());
    }

    public function repartitionMode(): JsonResponse
    {
        return response()->json($this->service->getRepartitionParMode());
    }
}
