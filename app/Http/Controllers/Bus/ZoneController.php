<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreZoneRequest;
use App\Http\Requests\UpdateZoneRequest;
use App\Services\ZoneService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ZoneController extends Controller
{
    protected ZoneService $service;

    public function __construct(ZoneService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.zones.index', ['page_title' => 'Zones de bus']);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'etat', 'annee_id', 'per_page']);
        $result = $this->service->listZones($filters);
        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['pagination']
        ]);
    }

    public function store(StoreZoneRequest $request): JsonResponse
    {
        try {
            $zone = $this->service->createZone($request->validated());
            return response()->json(['success' => true, 'message' => 'Zone créée', 'data' => $zone], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $zone = $this->service->getZone($id);
            return response()->json(['success' => true, 'data' => $zone]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Zone non trouvée'], 404);
        }
    }

    public function update(UpdateZoneRequest $request, int $id): JsonResponse
    {
        try {
            $zone = $this->service->updateZone($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Zone mise à jour', 'data' => $zone]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteZone($id);
            return response()->json(['success' => true, 'message' => 'Zone désactivée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function select(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getForSelect()]);
    }
}
