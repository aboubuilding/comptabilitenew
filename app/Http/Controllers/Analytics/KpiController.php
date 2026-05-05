<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\DashboardKpiService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class KpiController extends Controller
{
    protected DashboardKpiService $kpiService;

    public function __construct(DashboardKpiService $kpiService)
    {
        $this->kpiService = $kpiService;
    }

    /**
     * Vue principale du tableau de bord
     */
    public function index(): View
    {
        return view('admin.kpi.index', ['page_title' => 'Tableau de bord KPI']);
    }

    /**
     * API : Récupérer tous les KPI (JSON)
     */
    public function getAll(): JsonResponse
    {
        $kpi = $this->kpiService->getAllKpi();
        return response()->json(['success' => true, 'data' => $kpi]);
    }
}
