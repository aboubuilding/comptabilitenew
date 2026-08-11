<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Models\User;
use App\Models\Annee;
use App\Types\StatutAnnee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Authentifier un utilisateur
     */
    public function authenticate(string $login, string $password): array
    {
        try {
            // Recherche de l'utilisateur par login ou email
            $user = User::where('login', $login)
                ->orWhere('email', $login)
                ->first();

            if (!$user) {
                Log::warning('Tentative de connexion échouée: utilisateur non trouvé', ['login' => $login]);
                return [
                    'success' => false,
                    'code'    => 'USER_NOT_FOUND',
                    'message' => 'Identifiant ou mot de passe incorrect.',
                ];
            }

            // Vérification du mot de passe
            if (!Hash::check($password, $user->mot_passe)) {
                Log::warning('Tentative de connexion échouée: mot de passe incorrect', ['user_id' => $user->id]);
                return [
                    'success' => false,
                    'code'    => 'INVALID_PASSWORD',
                    'message' => 'Identifiant ou mot de passe incorrect.',
                ];
            }

            // Vérification de l'état du compte (etat = 1 pour actif)
            if ($user->etat !== 1) {
                Log::warning('Tentative de connexion sur compte inactif', ['user_id' => $user->id]);
                return [
                    'success' => false,
                    'code'    => 'ACCOUNT_INACTIVE',
                    'message' => 'Votre compte est désactivé. Contactez l\'administrateur.',
                ];
            }

            // ── Gestion de l'année active ──
            $annee = $this->getActiveYear();

            if (!$annee) {
                Log::error('Aucune année active trouvée');
                return [
                    'success' => false,
                    'code'    => 'NO_ACTIVE_YEAR',
                    'message' => 'Aucune année scolaire active. Veuillez contacter l\'administrateur.',
                ];
            }

            Session::put('annee_id', $annee->id);

            // ── Session utilisateur ──
            Session::put('LoginUser', $user->id);

            Log::info('Connexion réussie', [
                'user_id' => $user->id,
                'login' => $user->login,
                'annee_id' => $annee->id,
                'annee_libelle' => $annee->libelle
            ]);

            return [
                'success' => true,
                'user'    => $user,
                'annee'   => $annee,
                'code'    => 'SUCCESS',
                'message' => 'Connexion réussie ! Bienvenue ' . ($user->prenom ?? $user->nom ?? $user->login),
                'redirect' => route('tableau'),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'authentification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'login' => $login
            ]);

            return [
                'success' => false,
                'code'    => 'ERROR',
                'message' => 'Une erreur technique est survenue. Veuillez réessayer ou contacter le support.',
            ];
        }
    }

    /**
     * Récupérer l'année active (statut OUVERT)
     *
     * @return Annee|null
     */
    public function getActiveYear(): ?Annee
    {
        // 1. Recherche d'une année avec statut OUVERT
        $annee = Annee::where('etat', 1)
            ->where('statut_annee', StatutAnnee::OUVERT)
            ->first();

        // 2. Si aucune année ouverte, prendre la première année active
        if (!$annee) {
            $annee = Annee::where('etat', 1)
                ->orderBy('date_rentree', 'desc')
                ->first();
        }

        // 3. Si aucune année active, essayer de créer une année par défaut
        if (!$annee) {
            $annee = $this->createDefaultYear();
        }

        return $annee;
    }

    /**
     * Créer une année par défaut
     *
     * @return Annee
     */
    private function createDefaultYear(): Annee
    {
        $currentYear = Carbon::now()->year;

        // Vérifier si on est en période de rentrée (septembre-décembre)
        $month = Carbon::now()->month;
        $startYear = $month >= 9 ? $currentYear : $currentYear - 1;

        $annee = Annee::create([
            'libelle' => 'Année ' . $startYear . '-' . ($startYear + 1),
            'date_rentree' => Carbon::create($startYear, 9, 1),
            'date_fin' => Carbon::create($startYear + 1, 8, 31),
            'date_ouverture_inscription' => Carbon::create($startYear, 6, 1),
            'date_fermeture_reinscription' => Carbon::create($startYear + 1, 1, 31),
            'statut_annee' => StatutAnnee::OUVERT,
            'etat' => 1,
        ]);

        Log::info('Année par défaut créée automatiquement', [
            'annee_id' => $annee->id,
            'libelle' => $annee->libelle
        ]);

        return $annee;
    }

    /**
     * Vérifier si une année est ouverte
     *
     * @param Annee $annee
     * @return bool
     */
    public function isYearOpen(Annee $annee): bool
    {
        return $annee->statut_annee === StatutAnnee::OUVERT;
    }

    /**
     * Vérifier si une année est clôturée
     *
     * @param Annee $annee
     * @return bool
     */
    public function isYearClosed(Annee $annee): bool
    {
        return $annee->statut_annee === StatutAnnee::CLOTURE;
    }

    /**
     * Vérifier si l'année actuelle est ouverte
     *
     * @return bool
     */
    public function isCurrentYearOpen(): bool
    {
        $annee = $this->getCurrentYear();
        if (!$annee) {
            return false;
        }

        return $this->isYearOpen($annee);
    }

    /**
     * Déconnecter l'utilisateur
     */
    public function logout(): void
    {
        $userId = Session::get('LoginUser');
        if ($userId) {
            Log::info('Déconnexion', ['user_id' => $userId]);
        }

        Session::forget('LoginUser');
        Session::forget('annee_id');
    }

    /**
     * Récupérer l'utilisateur connecté
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        $userId = Session::get('LoginUser');
        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }

    /**
     * Vérifier si un utilisateur est connecté
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return Session::has('LoginUser') && $this->getUser() !== null;
    }

    /**
     * Vérifier si l'utilisateur connecté a un rôle spécifique
     *
     * @param int $role
     * @return bool
     */
    public function hasRole(int $role): bool
    {
        $user = $this->getUser();
        return $user && $user->role === $role;
    }

    /**
     * Vérifier si l'utilisateur connecté a l'un des rôles spécifiés
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        return in_array($user->role, $roles);
    }

    /**
     * Vérifier si l'utilisateur est administrateur
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(1); // Rôle administrateur
    }

    /**
     * Récupérer l'année en cours (depuis la session)
     *
     * @return Annee|null
     */
    public function getCurrentYear(): ?Annee
    {
        $anneeId = Session::get('annee_id');
        if (!$anneeId) {
            // Si pas d'année en session, essayer de récupérer l'année active
            $annee = $this->getActiveYear();
            if ($annee) {
                Session::put('annee_id', $annee->id);
                return $annee;
            }
            return null;
        }

        return Annee::find($anneeId);
    }

    /**
     * Changer l'année en cours
     *
     * @param int $anneeId
//     * @return bool
//     */
    public function setCurrentYear(int $anneeId): bool
    {
        $annee = Annee::where('id', $anneeId)
            ->where('etat', 1)
            ->first();

        if (!$annee) {
            Log::warning('Tentative de changement vers une année invalide', ['annee_id' => $anneeId]);
            return false;
        }

        Session::put('annee_id', $anneeId);

        Log::info('Changement d\'année', [
            'user_id' => Session::get('LoginUser'),
            'annee_id' => $anneeId,
            'annee_libelle' => $annee->libelle
        ]);

        return true;
    }

    /**
     * Obtenir la liste des années disponibles (actives)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableYears()
    {
        return Annee::where('etat', 1)
            ->orderBy('date_rentree', 'desc')
            ->get();
    }

    /**
     * Obtenir la liste des années pour un select
     *
     * @return array
     */
    public function getYearsForSelect(): array
    {
        return Annee::where('etat', 1)
            ->orderBy('date_rentree', 'desc')
            ->pluck('libelle', 'id')
            ->toArray();
    }

    /**
     * Récupérer les informations de l'utilisateur pour l'affichage
     *
     * @return array|null
     */
    public function getUserInfo(): ?array
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        $annee = $this->getCurrentYear();

        return [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'login' => $user->login,
            'email' => $user->email,
            'photo' => $user->photo,
            'role' => $user->role,
            'nom_complet' => trim($user->prenom . ' ' . $user->nom),
            'est_admin' => $this->isAdmin(),
            'annee_courante' => $annee ? [
                'id' => $annee->id,
                'libelle' => $annee->libelle,
                'statut' => $annee->statut_annee,
                'statut_label' => StatutAnnee::getLabel($annee->statut_annee),
                'est_ouverte' => $this->isYearOpen($annee),
            ] : null,
        ];
    }

    /**
     * Vérifier les permissions d'accès à une fonctionnalité
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        // Définition des permissions par rôle
        $permissions = [
            1 => ['*'], // Administrateur : toutes les permissions
            2 => ['view', 'create', 'edit', 'delete'], // Gestionnaire
            3 => ['view', 'create', 'edit'], // Secrétaire
            4 => ['view'], // Enseignant / Utilisateur simple
        ];

        // Si l'utilisateur a le rôle 1 (admin), il a accès à tout
        if ($user->role === 1) {
            return true;
        }

        // Vérifier si la permission est dans la liste du rôle
        return isset($permissions[$user->role]) &&
            (in_array('*', $permissions[$user->role]) ||
                in_array($permission, $permissions[$user->role]));
    }

    /**
     * Vérifier si l'utilisateur a accès à une fonctionnalité (alias)
     *
     * @param string $permission
     * @return bool
     */
    public function can(string $permission): bool
    {
        return $this->hasPermission($permission);
    }

    /**
     * Rafraîchir les données de session de l'utilisateur
     *
     * @return void
     */
    public function refreshSession(): void
    {
        $userId = Session::get('LoginUser');
        if ($userId) {
            // Vérifier que l'utilisateur existe toujours
            $user = User::find($userId);
            if (!$user || $user->etat !== 1) {
                // Si l'utilisateur n'existe plus ou est inactif, déconnecter
                $this->logout();
                Log::warning('Session expirée : utilisateur supprimé ou inactif', ['user_id' => $userId]);
            }
        }
    }
}
