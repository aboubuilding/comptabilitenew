<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChauffeurRequest;
use App\Http\Requests\UpdateChauffeurRequest;
use App\Services\ChauffeurService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ChauffeurController extends Controller
{
    protected ChauffeurService $service;

    public function __construct(ChauffeurService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.chauffeurs.index', [
            'page_title' => 'Gestion des chauffeurs'
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'statut', 'annee_id', 'per_page']);
        $result = $this->service->listChauffeurs($filters);
        return response()->json([
            'success' => true,
            'data'    => $result['data'],
            'meta'    => $result['pagination']
        ]);
    }

    public function store(StoreChauffeurRequest $request): JsonResponse
    {
        try {
            $chauffeur = $this->service->createChauffeur($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Chauffeur ajouté',
                'data'    => $chauffeur
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $chauffeur = $this->service->getChauffeur($id);
            return response()->json(['success' => true, 'data' => $chauffeur]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Chauffeur non trouvé'], 404);
        }
    }

    public function update(UpdateChauffeurRequest $request, int $id): JsonResponse
    {
        try {
            $chauffeur = $this->service->updateChauffeur($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Chauffeur mis à jour',
                'data'    => $chauffeur
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            if ($this->service->hasActiveAffectations($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce chauffeur a des affectations actives. Impossible de le désactiver.'
                ], 409);
            }
            $this->service->deleteChauffeur($id);
            return response()->json(['success' => true, 'message' => 'Chauffeur désactivé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function select(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getForSelect()]);
    }
}
