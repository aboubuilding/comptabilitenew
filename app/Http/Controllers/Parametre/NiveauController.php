<?php

namespace App\Http\Controllers\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\NiveauRequest;
use App\Models\Niveau;
use App\Models\Cycle;
use App\Repositories\Interfaces\NiveauRepositoryInterface;
use App\Repositories\Interfaces\CycleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NiveauController extends Controller
{
    protected NiveauRepositoryInterface $repository;
    protected CycleRepositoryInterface $cycleRepository;

    public function __construct(
        NiveauRepositoryInterface $repository,
        CycleRepositoryInterface $cycleRepository
    ) {
        $this->repository = $repository;
        $this->cycleRepository = $cycleRepository;
    }

    /**
     * Liste des niveaux
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'cycle_id' => $request->get('cycle_id'),
            'etat' => $request->get('etat'),
        ];

        $niveaux = $this->repository->getAllWithFilters($filters);
        $stats = $this->repository->getStats();
        $cycles = $this->cycleRepository->getActiveCycles();

        return view('admin.niveaux.index', compact('niveaux', 'stats', 'cycles'));
    }

    /**
     * Afficher les détails d'un niveau
     */
    public function show(Niveau $niveau)
    {
        $niveau->load('cycle');

        return response()->json([
            'success' => true,
            'data' => $niveau,
            'etat_label' => $niveau->etat_label,
            'etat_badge_class' => $niveau->etat_badge_class,
            'cycle_libelle' => $niveau->cycle_libelle,
            'can_delete' => $this->repository->canDelete($niveau),
        ]);
    }

    /**
     * Enregistrer un nouveau niveau
     */
    public function store(NiveauRequest $request)
    {
        try {
            $data = $request->validatedWithDefaults();
            $niveau = $this->repository->createWithValidation($data);

            return response()->json([
                'success' => true,
                'message' => 'Niveau créé avec succès.',
                'data' => $niveau
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du niveau', [
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
     * Mettre à jour un niveau
     */
    public function update(NiveauRequest $request, Niveau $niveau)
    {
        try {
            $data = $request->validatedWithDefaults();
            $niveau = $this->repository->updateWithValidation($niveau, $data);

            return response()->json([
                'success' => true,
                'message' => 'Niveau mis à jour avec succès.',
                'data' => $niveau
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du niveau', [
                'error' => $e->getMessage(),
                'niveau_id' => $niveau->id,
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un niveau
     */
    public function destroy(Niveau $niveau)
    {
        try {
            if (!$this->repository->canDelete($niveau)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce niveau ne peut pas être supprimé car il est utilisé.'
                ], 422);
            }

            $this->repository->delete($niveau->id);

            return response()->json([
                'success' => true,
                'message' => 'Niveau supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du niveau', [
                'error' => $e->getMessage(),
                'niveau_id' => $niveau->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un niveau
     */
    public function toggleActive(Niveau $niveau)
    {
        try {
            $niveau = $this->repository->toggleActive($niveau);

            return response()->json([
                'success' => true,
                'message' => $niveau->etat === 1 ? 'Niveau activé.' : 'Niveau désactivé.',
                'data' => $niveau
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du toggle du niveau', [
                'error' => $e->getMessage(),
                'niveau_id' => $niveau->id
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

    /**
     * Obtenir les niveaux par cycle (API)
     */
    public function getByCycle(Request $request)
    {
        $request->validate([
            'cycle_id' => 'required|integer|exists:cycles,id'
        ]);

        try {
            $niveaux = $this->repository->getByCycle($request->cycle_id);

            return response()->json([
                'success' => true,
                'data' => $niveaux
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }
}
