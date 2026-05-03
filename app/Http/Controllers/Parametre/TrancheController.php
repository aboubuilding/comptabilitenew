<?php

namespace App\Http\Controllers\Admin\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrancheRequest;
use App\Http\Requests\UpdateTrancheRequest;
use App\Services\TrancheService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TrancheController extends Controller
{
    protected TrancheService $service;

    public function __construct(TrancheService $service)
    {
        $this->service = $service;
    }

    /**
     * Récupère l'ID de l'année en cours depuis la session
     */
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Affiche la vue (server‑side) avec les tranches de l'année courante
     */
    public function index(): View
    {
        $anneeId = $this->getCurrentAnneeId();
        $tranches = $anneeId ? $this->service->getAllFormatted($anneeId) : collect();

        return view('admin.tranches.index', [
            'tranches'   => $tranches,
            'page_title' => 'Tranches de paiement',
        ]);
    }

    /**
     * API : Liste JSON des tranches de l'année courante (formatée)
     */
    public function list(): JsonResponse
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return response()->json(['success' => false, 'message' => 'Aucune année scolaire en session.'], 400);
        }

        $tranches = $this->service->getAllFormatted($anneeId);
        return response()->json(['success' => true, 'data' => $tranches]);
    }

    /**
     * API : Liste JSON des tranches d'un frais spécifique (pour l'année courante)
     */
    public function listByFrais(int $fraisEcoleId): JsonResponse
    {
        // Vérifier que le frais appartient bien à l'année courante (optionnel)
        $anneeId = $this->getCurrentAnneeId();
        if ($anneeId) {
            $frais = \DB::table('frais_ecoles')->where('id', $fraisEcoleId)->where('annee_id', $anneeId)->first();
            if (!$frais) {
                return response()->json(['success' => false, 'message' => 'Frais non trouvé pour cette année.'], 404);
            }
        }

        $tranches = $this->service->getByFraisEcole($fraisEcoleId);
        return response()->json(['success' => true, 'data' => $tranches]);
    }

    /**
     * API : Détail d'une tranche
     */
    public function show(int $id): JsonResponse
    {
        try {
            $tranche = $this->service->show($id);
            return response()->json(['success' => true, 'data' => $tranche]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tranche non trouvée'], 404);
        }
    }

    /**
     * API : Création – aucun champ année, mais on peut vérifier l'appartenance via le frais
     */
    public function store(StoreTrancheRequest $request): JsonResponse
    {
        try {
            // Vérifier que le frais associé appartient à l'année courante (sécurité)
            $anneeId = $this->getCurrentAnneeId();
            if ($anneeId) {
                $frais = \DB::table('frais_ecoles')
                    ->where('id', $request->frais_ecole_id)
                    ->where('annee_id', $anneeId)
                    ->first();
                if (!$frais) {
                    return response()->json(['success' => false, 'message' => 'Le frais sélectionné n\'appartient pas à l\'année en cours.'], 422);
                }
            }

            $tranche = $this->service->store($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tranche créée avec succès',
                'data'    => $tranche->only(['id', 'libelle'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Mise à jour
     */
    public function update(UpdateTrancheRequest $request, int $id): JsonResponse
    {
        try {
            // Vérification similaire pour l'année courante
            $anneeId = $this->getCurrentAnneeId();
            if ($anneeId && $request->has('frais_ecole_id')) {
                $frais = \DB::table('frais_ecoles')
                    ->where('id', $request->frais_ecole_id)
                    ->where('annee_id', $anneeId)
                    ->first();
                if (!$frais) {
                    return response()->json(['success' => false, 'message' => 'Le frais sélectionné n\'appartient pas à l\'année en cours.'], 422);
                }
            }

            $this->service->update($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Tranche modifiée']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Suppression
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if ($this->service->hasRelatedData($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette tranche est utilisée dans des échéances ou factures. Suppression impossible.'
                ], 409);
            }

            $this->service->destroy($id);
            return response()->json(['success' => true, 'message' => 'Tranche supprimée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}