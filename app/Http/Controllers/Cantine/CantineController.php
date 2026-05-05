<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\InscriptionCantineRequest;
use App\Http\Requests\AbandonCantineRequest;
use App\Services\CantineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CantineController extends Controller
{
    protected CantineService $service;

    public function __construct(CantineService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.cantine.index', ['page_title' => 'Inscriptions à la cantine']);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['eleve_search', 'classe_id', 'niveau_id', 'per_page']);
        $result = $this->service->listeInscrits($filters);
        return response()->json($result);
    }

    public function store(InscriptionCantineRequest $request): JsonResponse
    {
        try {
            $inscription = $this->service->inscrireCantine(
                $request->inscription_id,
                $request->date_debut,
                $request->frais_ecole_id
            );
            return response()->json(['success' => true, 'message' => 'Inscription cantine enregistrée', 'data' => $inscription], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $inscription = $this->service->getInscription($id);
            return response()->json(['success' => true, 'data' => $inscription]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Inscription non trouvée'], 404);
        }
    }

    public function abandon(AbandonCantineRequest $request, int $id): JsonResponse
    {
        try {
            $this->service->abandonnerCantine($id, $request->motif, auth()->id());
            return response()->json(['success' => true, 'message' => 'Abandon enregistré']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
