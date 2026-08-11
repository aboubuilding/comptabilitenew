<?php

namespace App\Http\Controllers\Parametre ;

use App\Http\Requests\AnneeRequest;
use App\Http\Controllers\Controller;
use App\Models\Annee;
use App\Repositories\Interfaces\AnneeRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnneeController extends Controller
{
    protected AnneeRepositoryInterface $repository;

    public function __construct(AnneeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Liste des années scolaires
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'statut_annee' => $request->get('statut_annee'),
            'etat' => $request->get('etat'),
        ];

        $annees = $this->repository->getAllWithFilters($filters);
        $stats = $this->repository->getStats();

        return view('admin.annees.index', compact('annees', 'stats'));
    }

    /**
     * Afficher les détails d'une année
     */
    public function show(Annee $annee)
    {
        return response()->json([
            'success' => true,
            'data' => $annee,
            'statut_label' => $annee->statut_label,
            'statut_badge_class' => $annee->statut_badge_class,
            'can_delete' => $this->repository->canDelete($annee),
        ]);
    }

    /**
     * Enregistrer une nouvelle année
     */
    public function store(AnneeRequest $request)
    {
        try {
            $data = $request->validatedWithDefaults();
            $annee = $this->repository->createWithValidation($data);

            return response()->json([
                'success' => true,
                'message' => 'Année créée avec succès.',
                'data' => $annee
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'année', [
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
     * Mettre à jour une année
     */
    public function update(AnneeRequest $request, Annee $annee)
    {
        try {
            $data = $request->validatedWithDefaults();
            $annee = $this->repository->updateWithValidation($annee, $data);

            return response()->json([
                'success' => true,
                'message' => 'Année mise à jour avec succès.',
                'data' => $annee
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'année', [
                'error' => $e->getMessage(),
                'annee_id' => $annee->id,
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une année
     */
    public function destroy(Annee $annee)
    {
        try {
            if (!$this->repository->canDelete($annee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette année ne peut pas être supprimée car elle est utilisée.'
                ], 422);
            }

            $this->repository->delete($annee->id);

            return response()->json([
                'success' => true,
                'message' => 'Année supprimée avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'année', [
                'error' => $e->getMessage(),
                'annee_id' => $annee->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver une année
     */
    public function toggleActive(Annee $annee)
    {
        try {
            $annee = $this->repository->toggleActive($annee);

            return response()->json([
                'success' => true,
                'message' => $annee->etat === 1 ? 'Année activée.' : 'Année désactivée.',
                'data' => $annee
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du toggle de l\'année', [
                'error' => $e->getMessage(),
                'annee_id' => $annee->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Définir une année comme ouverte
     */
    public function setActive(Annee $annee)
    {
        try {
            $annee = $this->repository->setAsOpen($annee);

            return response()->json([
                'success' => true,
                'message' => 'Année définie comme ouverte avec succès.',
                'data' => $annee
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'ouverture de l\'année', [
                'error' => $e->getMessage(),
                'annee_id' => $annee->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer le statut d'une année
     */
    public function toggleStatus(Request $request, Annee $annee)
    {
        $request->validate([
            'statut_annee' => 'required|integer|in:1,2,3'
        ]);

        try {
            $annee = $this->repository->changeStatus($annee, $request->statut_annee);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès.',
                'data' => $annee
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut', [
                'error' => $e->getMessage(),
                'annee_id' => $annee->id
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
