<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiFilterRequest;
use App\Services\DecaissementService;
use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;

class DecaissementController extends Controller
{
    public function __construct(
        private DecaissementService $service,
        private ReportingService $reportingService
    ) {}

    public function index(ApiFilterRequest $request): JsonResponse
    {
        $decaissements = $this->service->getDecaissementsWithStats($request->validated());
        return response()->json([
            'success' => true,
            'data'    => $decaissements,
            'meta'    => ['total' => $decaissements->count(), 'montant_total' => $decaissements->sum('montant')],
        ]);
    }

    public function stats(ApiFilterRequest $request): JsonResponse
    {
        $period = $request->validated()['period'] ?? 'month';
        $stats  = $this->reportingService->getDecaissementsTotalsByPeriod($period, $request->validated());

        return response()->json(['success' => true, 'data' => ['par_periode' => $stats]]);
    }
}