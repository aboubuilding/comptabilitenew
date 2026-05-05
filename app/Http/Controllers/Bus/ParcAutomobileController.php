<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoitureRequest;
use App\Http\Requests\UpdateVoitureRequest;
use App\Http\Requests\StoreChauffeurRequest;
use App\Http\Requests\UpdateChauffeurRequest;
use App\Http\Requests\StoreAffectationRequest;
use App\Http\Requests\UpdateAffectationRequest;
use App\Http\Requests\StoreEntretienRequest;
use App\Http\Requests\StoreCarburantRequest;
use App\Services\ParcAutomobileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ParcAutomobileController extends Controller
{
    protected ParcAutomobileService $service;

    public function __construct(ParcAutomobileService $service)
    {
        $this->service = $service;
    }

    // ======================= VUES =======================
    public function index(): View
    {
        return view('admin.parc.index', ['page_title' => 'Parc Automobile']);
    }

    // ======================= VOITURES =======================
    public function voituresList(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'statut', 'per_page']);
        return response()->json($this->service->listeVoitures($filters));
    }

    public function voitureShow(int $id): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->getVoiture($id)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Véhicule non trouvé'], 404);
        }
    }

    public function voitureStore(StoreVoitureRequest $request): JsonResponse
    {
        try {
            $voiture = $this->service->createVoiture($request->validated());
            return response()->json(['success' => true, 'message' => 'Véhicule ajouté', 'data' => $voiture], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function voitureUpdate(UpdateVoitureRequest $request, int $id): JsonResponse
    {
        try {
            $voiture = $this->service->updateVoiture($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Véhicule modifié', 'data' => $voiture]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function voitureDestroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteVoiture($id);
            return response()->json(['success' => true, 'message' => 'Véhicule supprimé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ======================= CHAUFFEURS =======================
    public function chauffeursList(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'statut', 'per_page']);
        return response()->json($this->service->listeChauffeurs($filters));
    }

    public function chauffeurStore(StoreChauffeurRequest $request): JsonResponse
    {
        try {
            $chauffeur = $this->service->createChauffeur($request->validated());
            return response()->json(['success' => true, 'message' => 'Chauffeur ajouté', 'data' => $chauffeur], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function chauffeurUpdate(UpdateChauffeurRequest $request, int $id): JsonResponse
    {
        try {
            $chauffeur = $this->service->updateChauffeur($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Chauffeur modifié', 'data' => $chauffeur]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function chauffeurDestroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteChauffeur($id);
            return response()->json(['success' => true, 'message' => 'Chauffeur supprimé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ======================= AFFECTATIONS =======================
    public function affectationsList(Request $request): JsonResponse
    {
        $filters = $request->only(['voiture_id', 'chauffeur_id', 'en_cours', 'per_page']);
        return response()->json($this->service->listeAffectations($filters));
    }

    public function affectationStore(StoreAffectationRequest $request): JsonResponse
    {
        try {
            $affectation = $this->service->createAffectation($request->validated());
            return response()->json(['success' => true, 'message' => 'Affectation créée', 'data' => $affectation], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function affectationTerminer(Request $request, int $id): JsonResponse
    {
        try {
            $motif = $request->input('motif');
            $affectation = $this->service->terminerAffectation($id, $motif);
            return response()->json(['success' => true, 'message' => 'Affectation terminée', 'data' => $affectation]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ======================= ENTRETIENS =======================
    public function entretiensList(Request $request): JsonResponse
    {
        $filters = $request->only(['voiture_id', 'date_debut', 'date_fin', 'per_page']);
        return response()->json($this->service->listeEntretiens($filters));
    }

    public function entretienStore(StoreEntretienRequest $request): JsonResponse
    {
        try {
            $entretien = $this->service->createEntretien($request->validated());
            return response()->json(['success' => true, 'message' => 'Entretien enregistré', 'data' => $entretien], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ======================= CARBURANT =======================
    public function carburantsList(Request $request): JsonResponse
    {
        $filters = $request->only(['voiture_id', 'date_debut', 'date_fin', 'per_page']);
        return response()->json($this->service->listeCarburants($filters));
    }

    public function carburantStore(StoreCarburantRequest $request): JsonResponse
    {
        try {
            $carburant = $this->service->createCarburant($request->validated());
            return response()->json(['success' => true, 'message' => 'Plein enregistré', 'data' => $carburant], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ======================= ASSURANCES =======================
    public function assurancesList(Request $request): JsonResponse
    {
        $filters = $request->only(['voiture_id', 'a_expirer', 'per_page']);
        return response()->json($this->service->listeAssurances($filters));
    }

    public function assuranceStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'voiture_id' => 'required|exists:voitures,id',
            'compagnie_assurance' => 'required|string|max:255',
            'numero_contrat' => 'required|string|max:255|unique:assurances_vehicules,numero_contrat',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'prime' => 'required|numeric|min:0',
            'type_assurance' => 'required|string|max:255',
        ]);
        try {
            $assurance = $this->service->createAssurance($validated);
            return response()->json(['success' => true, 'message' => 'Assurance enregistrée', 'data' => $assurance], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
