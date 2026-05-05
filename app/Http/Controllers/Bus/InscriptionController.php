<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\InscriptionBusRequest;
use App\Http\Requests\AbandonBusRequest;
use App\Services\BusService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class InscriptionController extends Controller
{
    protected BusService $service;

    public function __construct(BusService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.bus.index', ['page_title' => 'Abonnements bus']);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['eleve_search', 'classe_id', 'niveau_id', 'per_page']);
        $result = $this->service->listeInscritsBus($filters);
        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'aggregates' => $result['aggregates'],
            'meta' => $result['pagination']
        ]);
    }

    public function store(InscriptionBusRequest $request): JsonResponse
    {
        try {
            $abonnement = $this->service->inscrireBus(
                $request->inscription_id,
                $request->date_debut,
                $request->montant_mensuel
            );
            return response()->json(['success' => true, 'message' => 'Inscription bus enregistrée', 'data' => $abonnement], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $abonnement = $this->service->getAbonnement($id);
            return response()->json(['success' => true, 'data' => $abonnement]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Abonnement non trouvé'], 404);
        }
    }

    public function abandon(AbandonBusRequest $request, int $id): JsonResponse
    {
        try {
            $this->service->abandonnerBus($id, $request->motif, auth()->id());
            return response()->json(['success' => true, 'message' => 'Abonnement abandonné']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
