<?php

namespace App\Http\Controllers\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected UserRepositoryInterface $repository;

    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Liste des utilisateurs
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'role' => $request->get('role'),
            'etat' => $request->get('etat'),
        ];

        $users = $this->repository->getAllWithFilters($filters);
        $stats = $this->repository->getStats();

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Afficher les détails d'un utilisateur
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user,
            'role_label' => $user->role_label,
            'role_badge_class' => $user->role_badge_class,
            'etat_label' => $user->etat_label,
            'can_delete' => $this->repository->canDelete($user),
        ]);
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store(UserRequest $request)
    {
        try {
            $data = $request->validatedWithDefaults();
            $user = $this->repository->createWithValidation($data);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès.',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'utilisateur', [
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
     * Mettre à jour un utilisateur
     */
    public function update(UserRequest $request, User $user)
    {
        try {
            $data = $request->validatedWithDefaults();
            $user = $this->repository->updateWithValidation($user, $data);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès.',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'utilisateur', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        try {
            if (!$this->repository->canDelete($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer le dernier administrateur.'
                ], 422);
            }

            $this->repository->delete($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'utilisateur', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleActive(User $user)
    {
        try {
            $user = $this->repository->toggleActive($user);

            return response()->json([
                'success' => true,
                'message' => $user->etat === 1 ? 'Utilisateur activé.' : 'Utilisateur désactivé.',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du toggle de l\'utilisateur', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer le mot de passe d'un utilisateur
     */
    public function changePassword(Request $request, User $user)
    {
        $request->validate([
            'mot_passe' => 'required|string|min:6|confirmed',
        ]);

        try {
            $user = $this->repository->changePassword($user, $request->mot_passe);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe changé avec succès.',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de mot de passe', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
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
