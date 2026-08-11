<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    // Désactiver l'injection automatique de annee_id
    protected bool $autoInjectAnneId = false;

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer tous les utilisateurs avec filtres
     */
    public function getAllWithFilters(?array $filters = null): Collection
    {
        $query = $this->query();

        if (!empty($filters)) {
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('nom', 'LIKE', "%{$search}%")
                        ->orWhere('prenom', 'LIKE', "%{$search}%")
                        ->orWhere('login', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            if (isset($filters['role']) && $filters['role'] !== '') {
                $query->where('role', (int)$filters['role']);
            }

            if (isset($filters['etat']) && $filters['etat'] !== '') {
                $query->where('etat', (int)$filters['etat']);
            }
        }

        return $query->orderBy('nom', 'asc')->get();
    }

    /**
     * Récupérer les utilisateurs actifs
     */
    public function getActiveUsers(): Collection
    {
        return $this->activeQuery()
            ->orderBy('nom', 'asc')
            ->get();
    }

    /**
     * Récupérer les utilisateurs par rôle
     */
    public function getByRole(int $role): Collection
    {
        return $this->activeQuery()
            ->where('role', $role)
            ->orderBy('nom', 'asc')
            ->get();
    }

    /**
     * Récupérer les administrateurs
     */
    public function getAdmins(): Collection
    {
        return $this->getByRole(User::ROLE_ADMIN);
    }

    /**
     * Trouver un utilisateur par login
     */
    public function findByLogin(string $login): ?User
    {
        return $this->query()->where('login', $login)->first();
    }

    /**
     * Trouver un utilisateur par email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', $email)->first();
    }

    /**
     * Vérifier si un utilisateur peut être supprimé
     */
    public function canDelete(User $user): bool
    {
        // Ne pas supprimer le dernier admin
        if ($user->isAdmin()) {
            $adminCount = $this->query()->where('role', User::ROLE_ADMIN)->count();
            if ($adminCount <= 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Supprimer un utilisateur avec vérification
     */
    public function deleteWithCheck(User $user): bool
    {
        if (!$this->canDelete($user)) {
            Log::warning('Impossible de supprimer le dernier administrateur', [
                'user_id' => $user->id
            ]);
            return false;
        }

        return $this->delete($user->id);
    }

    /**
     * Obtenir les statistiques des utilisateurs
     */
    public function getStats(): array
    {
        return [
            'total' => $this->query()->count(),
            'actifs' => $this->activeQuery()->count(),
            'inactifs' => $this->query()->where('etat', self::SUPPRIME)->count(),
            'admins' => $this->getByRole(User::ROLE_ADMIN)->count(),
            'directeurs' => $this->getByRole(User::ROLE_DIRECTEUR)->count(),
            'comptables' => $this->getByRole(User::ROLE_COMPTABLE)->count(),
            'enseignants' => $this->getByRole(User::ROLE_ENSEIGNANT)->count(),
            'parents' => $this->getByRole(User::ROLE_PARENT)->count(),
        ];
    }

    /**
     * Créer un utilisateur avec validation
     */
    public function createWithValidation(array $data): User
    {
        // Hasher le mot de passe
        if (isset($data['mot_passe'])) {
            $data['mot_passe'] = Hash::make($data['mot_passe']);
        }

        return $this->create($data);
    }

    /**
     * Mettre à jour un utilisateur avec validation
     */
    public function updateWithValidation(User $user, array $data): User
    {
        // Hasher le mot de passe si fourni
        if (isset($data['mot_passe']) && !empty($data['mot_passe'])) {
            $data['mot_passe'] = Hash::make($data['mot_passe']);
        } else {
            unset($data['mot_passe']);
        }

        $this->update($user->id, $data);
        return $user->fresh();
    }



    /**
     * Changer le mot de passe
     */
    public function changePassword(User $user, string $newPassword): User
    {
        $user->mot_passe = Hash::make($newPassword);
        $user->save();

        Log::info('Mot de passe changé pour l\'utilisateur', [
            'user_id' => $user->id,
            'login' => $user->login
        ]);

        return $user->fresh();
    }
}
