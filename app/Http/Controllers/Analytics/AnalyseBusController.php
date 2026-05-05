<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\AnalyseBusService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AnalyseBusController extends Controller
{
    protected $service;

    public function __construct(AnalyseBusService $service)
    {
        $this->service = $service;
    }

    /**
     * Vue principale (page d'analyse du bus)
     */
    public function index(): View
    {
        return view('admin.analyse-bus.index', ['page_title' => 'Analyse du Transport Scolaire (Bus)']);
    }

    /**
     * API : retourne tous les indicateurs en JSON
     */
    public function getAll(): JsonResponse
    {
        $data = $this->service->getAll();
        return response()->json(['success' => true, 'data' => $data]);
    }
}
