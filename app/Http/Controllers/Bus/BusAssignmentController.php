<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\BusAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class BusAssignmentController extends Controller
{
    protected BusAssignmentService $service;

    public function __construct(BusAssignmentService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.bus.assignations', ['page_title' => 'Assignation des élèves aux bus']);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['statut', 'voiture_id', 'zone_id', 'search', 'per_page']);
        $result = $this->service->listAssignations($filters);
        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['pagination']
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'abonnement_bus_id' => 'required|exists:abonnements_bus,id',
            'voiture_id'        => 'required|exists:voitures,id',
            'zone_id'           => 'required|exists:zones,id',
            'date_debut'        => 'required|date',
            'date_fin'          => 'nullable|date|after_or_equal:date_debut',
            'sens'              => 'nullable|integer|in:1,2,3',
            'motif'             => 'nullable|string',
        ]);

        try {
            $assign = $this->service->assignerEleve(
                $request->abonnement_bus_id,
                $request->voiture_id,
                $request->zone_id,
                $request->date_debut,
                $request->date_fin,
                $request->sens ?? 3,
                $request->motif
            );
            return response()->json(['success' => true, 'message' => 'Assignation effectuée', 'data' => $assign], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $assign = $this->service->getAssignation($id);
            return response()->json(['success' => true, 'data' => $assign]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Assignation non trouvée'], 404);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->validate(['motif' => 'nullable|string']);
        try {
            $this->service->desactiverAssignation($id, $request->motif);
            return response()->json(['success' => true, 'message' => 'Assignation désactivée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getAbonnementsNonAssignes(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getAbonnementsNonAssignes()]);
    }
}
