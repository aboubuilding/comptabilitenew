<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\AnalyseCantineService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AnalyseCantineController extends Controller
{
    protected $service;

    public function __construct(AnalyseCantineService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale de l'analyse cantine
     */
    public function index(): View
    {
        return view('admin.analyse.cantine', [
            'page_title' => 'Analyse de la cantine'
        ]);
    }

    /**
     * API : Récupère tous les indicateurs
     */
    public function getAll(): JsonResponse
    {
        $data = [
            'indicateurs'       => $this->service->getIndicateursGlobaux(),
            'evolution'         => $this->service->getEvolutionMensuelle(),
            'performance_niveau'=> $this->service->getPerformanceParNiveau(),
            'rentabilite'       => $this->service->getRentabilite(),
            'cout_repas'        => $this->service->getCoutMoyenParRepas(),
            'top_repas'         => $this->service->getTopRepas(),
            'prevision_reel'    => $this->service->getPrevisionVsReel(),
        ];
        return response()->json(['success' => true, 'data' => $data]);
    }
}
