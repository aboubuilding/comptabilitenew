<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AlertController extends Controller
{
    protected $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Vue principale des alertes
     */
    public function index(): View
    {
        return view('admin.alerts.index', ['page_title' => 'Alertes comptables']);
    }

    /**
     * API : liste toutes les alertes
     */
    public function getAll(): JsonResponse
    {
        $alerts = $this->alertService->getAllAlerts();
        return response()->json(['success' => true, 'data' => $alerts]);
    }
}
