<?php

namespace App\Domain\Administration\Repositories;

use App\Domain\Administration\Models\User;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository des utilisateurs internes.
 */
class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /* ===================== Authentification ===================== */

    /** Recherche par login OU email — sans filtre d'état : AuthService distingue « inconnu » de « désactivé ». */
    public function findByLoginOrEmail(string $identifier): ?User
    {
        return $this->query()
            ->where(function ($q) use ($identifier) {
                $q->where('login', $identifier)->orWhere('email', $identifier);
            })
            ->first();
    }

    public function findByLogin(string $login): ?User
    {
        return $this->query()->where('login', $login)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', $email)->first();
    }

    public function recordLogin(User $user, ?string $ip): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();
    }

    public function changePassword(User $user, string $hashedPassword): User
    {
        $user->update(['mot_passe' => $hashedPassword]);

        return $user->fresh();
    }

    /* ===================== Rôles ===================== */

    /** Utilisateurs actifs portant un rôle donné (colonne historique `role`). */
    public function getByRole(RoleUtilisateur $role): Collection
    {
        return $this->activeQuery()->where('role', $role->value)->get();
    }

    public function getAdmins(): Collection
    {
        return $this->getByRole(RoleUtilisateur::Administrateur);
    }

    /** On ne désactive jamais le dernier administrateur. */
    public function canDelete(User $user): bool
    {
        return ! ($user->isAdmin() && $this->getAdmins()->count() <= 1);
    }

    public function listePaginee(array $filtres = [], int $perPage = 15)
    {
        $query = $this->activeQuery()
            ->with(['roleRelation', 'profilsComplementaires']);

        if (!empty($filtres['role_id'])) {
            $query->where('role_id', $filtres['role_id']);
        }

        if (!empty($filtres['recherche'])) {
            $r = $filtres['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('nom', 'like', "%{$r}%")
                  ->orWhere('prenom', 'like', "%{$r}%")
                  ->orWhere('login', 'like', "%{$r}%")
                  ->orWhere('email', 'like', "%{$r}%");
            });
        }

        return $query->orderBy('nom')->paginate($perPage);
    }

    public function listePourExport(array $filtres = [])
    {
        $query = $this->activeQuery()->with(['roleRelation', 'profilsComplementaires']);

        if (!empty($filtres['role_id'])) {
            $query->where('role_id', $filtres['role_id']);
        }

        return $query->orderBy('nom')->get();
    }
}