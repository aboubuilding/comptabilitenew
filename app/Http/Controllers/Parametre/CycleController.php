<?php

namespace App\Http\Controllers\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\CycleRequest;
use App\Models\Cycle;
use App\Repositories\Interfaces\CycleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CycleController extends Controller
{
    protected CycleRepositoryInterface $repository;

    public function __construct(CycleRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Liste des cycles
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'etat' => $request->get('etat'),
        ];

        $cycles = $this->repository->getAllWithFilters($filters);
        $stats = $this->repository->getStats();

        return view('admin.cycles.index', compact('cycles', 'stats'));
    }

    /**
     * Afficher les détails d'un cycle
     */
    public function show(Cycle $cycle)
    {
        return response()->json([
            'success' => true,
            'data' => $cycle,
            'etat_label' => $cycle->etat_label,
            'etat_badge_class' => $cycle->etat_badge_class,
            'can_delete' => $this->repository->canDelete($cycle),
        ]);
    }

    /**
     * Enregistrer un nouveau cycle
     */
    public function store(CycleRequest $request)
    {
        try {
            $data = $request->validatedWithDefaults();
            $cycle = $this->repository->createWithValidation($data);

            return response()->json([
                'success' => true,
                'message' => 'Cycle créé avec succès.',
                'data' => $cycle
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du cycle', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un cycle
     */
    public function update(CycleRequest $request, Cycle $cycle)
    {
        try {
            $data = $request->validatedWithDefaults();
            $cycle = $this->repository->updateWithValidation($cycle, $data);

            return response()->json([
                'success' => true,
                'message' => 'Cycle mis à jour avec succès.',
                'data' => $cycle
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du cycle', [
                'error' => $e->getMessage(),
                'cycle_id' => $cycle->id,
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un cycle
     */
    public function destroy(Cycle $cycle)
    {
        try {
            if (!$this->repository->canDelete($cycle)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce cycle ne peut pas être supprimé car il est utilisé.'
                ], 422);
            }

            $this->repository->delete($cycle->id);

            return response()->json([
                'success' => true,
                'message' => 'Cycle supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du cycle', [
                'error' => $e->getMessage(),
                'cycle_id' => $cycle->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un cycle
     */
    public function toggleActive(Cycle $cycle)
    {
        try {
            $cycle = $this->repository->toggleActive($cycle);

            return response()->json([
                'success' => true,
                'message' => $cycle->etat === 1 ? 'Cycle activé.' : 'Cycle désactivé.',
                'data' => $cycle
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du toggle du cycle', [
                'error' => $e->getMessage(),
                'cycle_id' => $cycle->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques (API)
     */
    public function stats()
    {
        try {
            $stats = $this->repository->getStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }
}
