<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrevisionRequest;
use App\Http\Requests\UpdatePrevisionRequest;
use App\Services\PrevisionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PrevisionController extends Controller
{
    protected $service;

    public function __construct(PrevisionService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.previsions.index', ['page_title' => 'Prévisions budgétaires']);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['annee_id', 'type', 'search', 'date_debut', 'date_fin', 'per_page']);
        $result = $this->service->listPrevisions($filters);
        return response()->json($result);
    }

    public function store(StorePrevisionRequest $request): JsonResponse
    {
        try {
            $prevision = $this->service->createPrevision($request->validated());
            return response()->json(['success' => true, 'message' => 'Prévision créée', 'data' => $prevision], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $prevision = $this->service->getPrevision($id);
            return response()->json(['success' => true, 'data' => $prevision]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Prévision non trouvée'], 404);
        }
    }

    public function update(UpdatePrevisionRequest $request, int $id): JsonResponse
    {
        try {
            $prevision = $this->service->updatePrevision($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Prévision mise à jour']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deletePrevision($id);
            return response()->json(['success' => true, 'message' => 'Prévision supprimée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
