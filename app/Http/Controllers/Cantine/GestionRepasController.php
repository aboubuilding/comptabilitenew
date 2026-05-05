<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\GestionRepasService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GestionRepasController extends Controller
{
    protected $service;

    public function __construct(GestionRepasService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.repas.index', ['page_title' => 'Gestion des repas']);
    }

    public function listMenus(Request $request): JsonResponse
    {
        $result = $this->service->listMenus($request->only(['type_repas', 'date_service', 'search', 'per_page']));
        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'libelle' => 'required|string',
            'date_service' => 'required|date',
            'type_repas' => 'required|integer',
            'quantite_prevue' => 'nullable|integer',
            'produits' => 'required|array',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|numeric|min:0.001',
        ]);
        try {
            $menu = $this->service->createMenu($request->only(['libelle', 'description', 'date_service', 'type_repas', 'quantite_prevue']), $request->produits);
            return response()->json(['success' => true, 'message' => 'Menu créé', 'data' => $menu], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'libelle' => 'sometimes|string',
            'date_service' => 'sometimes|date',
            'type_repas' => 'sometimes|integer',
            'quantite_prevue' => 'nullable|integer',
            'produits' => 'nullable|array',
        ]);
        try {
            $menu = $this->service->updateMenu($id, $request->only(['libelle', 'description', 'date_service', 'type_repas', 'quantite_prevue']), $request->produits);
            return response()->json(['success' => true, 'message' => 'Menu mis à jour']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function preparation(Request $request, int $menuId): JsonResponse
    {
        $request->validate([
            'nombre_parts' => 'required|integer|min:1',
            'cout_reel' => 'nullable|numeric',
            'observations' => 'nullable|string'
        ]);
        try {
            $prep = $this->service->enregistrerPreparation($menuId, $request->nombre_parts, $request->cout_reel, $request->observations);
            return response()->json(['success' => true, 'message' => 'Préparation enregistrée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function statistiques(Request $request): JsonResponse
    {
        $statsRepas = $this->service->getCoutMoyenRepas($request->date_debut, $request->date_fin);
        $statsInscrits = $this->service->getCoutParInscrit($request->date_debut, $request->date_fin);
        return response()->json([
            'cout_moyen_par_repas' => $statsRepas,
            'cout_par_inscrit' => $statsInscrits
        ]);
    }
}
