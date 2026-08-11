<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Affiche le formulaire de connexion.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLoginForm()
    {
        // Si l'utilisateur est déjà connecté, le rediriger vers le tableau de bord
        if ($this->authService->isLoggedIn()) {
            return redirect()->route('tableau');
        }

        return view('admin.login');
    }

    /**
     * Traite la tentative de connexion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Log pour déboguer
        Log::info('Tentative de connexion', ['login' => $request->input('login')]);

        // Validation des champs
        $request->validate([
            'login'    => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        // Appel du service d'authentification
        $result = $this->authService->authenticate(
            $request->input('login'),
            $request->input('password')
        );

        // Log du résultat
        Log::info('Résultat authentification', [
            'success' => $result['success'],
            'code' => $result['code'] ?? 'unknown'
        ]);

        // Gestion des requêtes AJAX / API
        if ($request->expectsJson() || $request->ajax()) {
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'code'    => $result['code'] ?? 'ERROR',
                    'message' => $result['message'] ?? 'Identifiants incorrects.',
                ], 401);
            }

            // Préparer les données de l'utilisateur pour la réponse
            $user = $result['user'];
            $userData = [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'login' => $user->login,
                'email' => $user->email,
                'role' => $user->role,
                'nom_complet' => trim(($user->prenom ?? '') . ' ' . ($user->nom ?? '')),
            ];

            return response()->json([
                'success'  => true,
                'message'  => $result['message'] ?? 'Connexion réussie !',
                'user'     => $userData,
                'annee'    => isset($result['annee']) && $result['annee'] ? [
                    'id' => $result['annee']->id,
                    'libelle' => $result['annee']->libelle,
                ] : null,
                'redirect' => $result['redirect'] ?? route('tableau'),
            ]);
        }

        // Échec de la connexion (requête non-AJAX)
        if (!$result['success']) {
            Log::warning('Tentative de connexion échouée', [
                'login' => $request->input('login'),
                'code' => $result['code'] ?? 'UNKNOWN',
                'ip' => $request->ip()
            ]);

            return back()
                ->withErrors(['login' => $result['message'] ?? 'Identifiants incorrects.'])
                ->withInput($request->only('login'));
        }

        // Succès (requête non-AJAX)
        Log::info('Connexion réussie', [
            'user_id' => $result['user']->id,
            'login' => $result['user']->login,
            'ip' => $request->ip()
        ]);

        // Utiliser la redirection prévue ou celle par défaut
        $redirect = $result['redirect'] ?? route('tableau');

        return redirect()->intended($redirect)
            ->with('success', $result['message'] ?? 'Connexion réussie !');
    }

    /**
     * Déconnecte l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // Récupérer les informations de l'utilisateur avant la déconnexion pour les logs
        $user = $this->authService->getUser();
        $userId = $user ? $user->id : null;
        $userLogin = $user ? $user->login : null;

        // Déconnecter via le service
        $this->authService->logout();

        // Nettoyage complet de la session pour plus de sécurité
        Session::flush();

        // Régénérer le token CSRF pour plus de sécurité
        $request->session()->regenerate();

        Log::info('Déconnexion', [
            'user_id' => $userId,
            'login' => $userLogin
        ]);

        return redirect()->route('login')
            ->with('success', 'Vous avez été déconnecté avec succès.');
    }

    /**
     * Vérifier l'état de la session (pour les requêtes AJAX)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSession(Request $request)
    {
        $isLoggedIn = $this->authService->isLoggedIn();

        if (!$isLoggedIn) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Session expirée',
            ]);
        }

        $user = $this->authService->getUser();
        $annee = $this->authService->getCurrentYear();

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'login' => $user->login,
                'email' => $user->email,
                'role' => $user->role,
                'nom_complet' => trim(($user->prenom ?? '') . ' ' . ($user->nom ?? '')),
            ],
            'annee' => $annee ? [
                'id' => $annee->id,
                'libelle' => $annee->libelle,
                'statut' => $annee->statut_annee,
                'est_ouverte' => $this->authService->isCurrentYearOpen(),
            ] : null,
        ]);
    }

    /**
     * Changer l'année en cours (utile pour les utilisateurs avec accès multi-années)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $anneeId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function switchYear(Request $request, int $anneeId)
    {
        // Vérifier que l'utilisateur est connecté
        if (!$this->authService->isLoggedIn()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être connecté pour changer d\'année.',
                ], 401);
            }
            return redirect()->route('login');
        }

        // Changer l'année
        $success = $this->authService->setCurrentYear($anneeId);

        if (!$success) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Année invalide ou inexistante.',
                ], 400);
            }
            return back()->with('error', 'Année invalide ou inexistante.');
        }

        $annee = $this->authService->getCurrentYear();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Année changée avec succès.',
                'annee' => [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ],
            ]);
        }

        return back()->with('success', 'Année changée avec succès.');
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $permission
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPermission(Request $request, string $permission)
    {
        $hasPermission = $this->authService->hasPermission($permission);

        return response()->json([
            'has_permission' => $hasPermission,
            'permission' => $permission,
        ]);
    }
}
