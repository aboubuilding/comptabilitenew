<?php

namespace App\Domain\Administration\Repositories;

use App\Domain\Administration\Models\User;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pas d'interface séparée (UserRepositoryInterface) : décision actée pour
 * tous les repositories de domaine tant qu'il n'y a qu'une seule
 * implémentation et pas de tests à mocker (voir doc d'architecture).
 *
 * Les méthodes *WithValidation de l'interface d'origine ont été retirées :
 * la validation de format vit dans le FormRequest, les règles métier
 * (hachage du mot de passe, unicité au-delà du format...) dans
 * UserService/AuthService — un Repository ne valide pas, il persiste.
 */
class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Recherche délibérément SANS activeQuery() : AuthService a besoin de
     * distinguer "utilisateur introuvable" de "compte inactif", donc les
     * comptes inactifs doivent rester trouvables ici.
     */
    public function findByLoginOrEmail(string $identifier): ?User
    {
        return $this->query()
            ->where('login', $identifier)
            ->orWhere('email', $identifier)
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

    public function getByRole(RoleUtilisateur $role): Collection
    {
        return $this->activeQuery()->where('role', $role->value)->get();
    }

    public function getAdmins(): Collection
    {
        return $this->getByRole(RoleUtilisateur::Administrateur);
    }

    public function getAllWithFilters(array $filters = []): Collection
    {
        return $this->activeQuery()
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                  ->orWhere('prenom', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            }))
            ->get();
    }

    /**
     * Vérification simple (un seul booléen) : reste au niveau Repository,
     * ne justifie pas encore un Service dédié — même règle que pour
     * Niveaux (un Service se justifie à partir d'une vraie décision
     * métier, pas d'un simple booléen consulté).
     */
    public function canDelete(User $user): bool
    {
        if ($user->role === RoleUtilisateur::Administrateur && $this->getAdmins()->count() <= 1) {
            return false; // jamais supprimer le dernier administrateur
        }

        return true;
    }

    public function deleteWithCheck(User $user): bool
    {
        return $this->canDelete($user) && $this->delete($user->id);
    }

    /**
     * Le mot de passe est déjà haché par l'appelant (UserService) — un
     * Repository ne décide pas de la politique de hachage, il persiste.
     */
    public function changePassword(User $user, string $hashedPassword): User
    {
        $user->update(['mot_passe' => $hashedPassword]);

        return $user->fresh();
    }

    public function recordLogin(User $user, ?string $ip): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    public function getStats(): array
    {
        return [
            'total'    => $this->activeQuery()->count(),
            'par_role' => $this->activeQuery()
                ->selectRaw('role, count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role')
                ->all(),
        ];
    }
}