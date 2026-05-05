<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAchatBoutiqueRequest;
use App\Services\AchatBoutiqueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AchatBoutiqueController extends Controller
{
    protected $service;

    public function __construct(AchatBoutiqueService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.achats-boutique.index', ['page_title' => 'Achats boutique']);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['date_debut', 'date_fin', 'fournisseur_id', 'reference', 'per_page']);
        $result = $this->service->listAchats($filters);
        return response()->json($result);
    }

    public function store(StoreAchatBoutiqueRequest $request): JsonResponse
    {
        try {
            $achat = $this->service->createAchat($request->validated());
            return response()->json(['success' => true, 'message' => 'Achat enregistré', 'data' => $achat], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateStatut(Request $request, $id): JsonResponse
    {
        $request->validate([
            'field' => 'required|in:statut_paiement,statut_livraison',
            'value' => 'required|integer|min:0|max:2',
        ]);
        $achat = $this->service->updateStatut($id, $request->field, $request->value);
        return response()->json(['success' => true, 'message' => 'Statut mis à jour']);
    }

    public function destroy($id): JsonResponse
    {
        $this->service->deleteAchat($id);
        return response()->json(['success' => true, 'message' => 'Achat annulé']);
    }
}
