<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\Repositories\UserRepository;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Scolarite\Services\AnneeService;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Ne gère QUE l'authentification. La résolution de l'année scolaire active
 * est déléguée à AnneeService (Domain\Scolarite) ; l'état "année courante"
 * vit dans AnneeScolaireContext (Support), pas ici.
 *
 * Utilise le guard natif Laravel (Auth::attempt/logout) plutôt qu'une
 * gestion de session manuelle : getAuthPassword() défini sur le modèle
 * User est ainsi réellement utilisé (l'ancienne version comparait le mot
 * de passe à la main et ne l'appelait jamais), et Auth::user()/le
 * middleware 'auth' standard redeviennent utilisables partout.
 *
 * IMPORTANT — à faire côté Controller, pas ici : appeler
 * $request->session()->regenerate() juste après un attempt() réussi.
 * Auth::attempt() ne régénère pas l'ID de session lui-même ; c'est cette
 * étape qui protège contre la fixation de session.
 */
class AuthService
{
    public function __construct(
        private UserRepository $users,
        private AnneeService $anneeService,
        private AnneeScolaireContext $anneeContext,
    ) {}

    public function attempt(string $login, string $password, bool $remember = false): array
    {
        $user = $this->users->findByLoginOrEmail($login);

        if (!$user) {
            Log::warning('Connexion échouée : utilisateur introuvable', ['login' => $login]);
            return $this->failure('USER_NOT_FOUND', 'Identifiant ou mot de passe incorrect.');
        }

        if (!$user->isActive()) {
            Log::warning('Connexion refusée : compte inactif', ['user_id' => $user->id]);
            return $this->failure('ACCOUNT_INACTIVE', "Votre compte est désactivé. Contactez l'administrateur.");
        }

        if (!Auth::attempt(['id' => $user->id, 'password' => $password], $remember)) {
            Log::warning('Connexion échouée : mot de passe incorrect', ['user_id' => $user->id]);
            return $this->failure('INVALID_PASSWORD', 'Identifiant ou mot de passe incorrect.');
        }

        $this->users->recordLogin($user, request()?->ip());

        $annee = $this->anneeService->resolveActive();
        $this->anneeContext->set($annee->id);
        session(['annee_id' => $annee->id]);

        Log::info('Connexion réussie', ['user_id' => $user->id, 'annee_id' => $annee->id]);

        return [
            'success'  => true,
            'code'     => 'SUCCESS',
            'user'     => $user,
            'annee'    => $annee,
            'message'  => 'Connexion réussie ! Bienvenue ' . ($user->prenom ?? $user->login),
            'redirect' => route('tableau'),
        ];
    }

    public function logout(): void
    {
        $userId = Auth::id();
        Auth::logout();

        if ($userId) {
            Log::info('Déconnexion', ['user_id' => $userId]);
        }
    }

    public function hasRole(RoleUtilisateur $role): bool
    {
        return Auth::user()?->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return Auth::user() && in_array(Auth::user()->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleUtilisateur::Administrateur);
    }

    /**
     * Consommé par HeaderComposer pour peupler $headerNomComplet,
     * $headerRoleLabel, etc.
     */
    public function currentUserInfo(): ?array
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return [
            'id'          => $user->id,
            'nom_complet' => $user->full_name,
            'email'       => $user->email,
            'role_label'  => $user->role_label,
            'est_admin'   => $this->isAdmin(),
        ];
    }

    private function failure(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}