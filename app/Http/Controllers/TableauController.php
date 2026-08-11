<?php
// app/Http/Controllers/TableauController.php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Models\User;
use App\Models\Annee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TableauController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Affiche le tableau de bord principal
     */
    public function tableau()
    {
        if (!$this->authService->isLoggedIn()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter.');
        }

        $user = $this->authService->getUser();
        $anneeCourante = $this->authService->getCurrentYear();
        $nomComplet = trim(($user->prenom ?? '') . ' ' . ($user->nom ?? ''));
        $userEmail = $user->email ?? 'user@ecoleinternationalemariam.net';

        $roleLabel = match($user->role ?? 0) {
            1 => 'Administrateur',
            2 => 'Directeur',
            3 => 'Comptable',
            4 => 'Admin Adjoint',
            5 => 'Caissier',
            6 => 'Secrétaire',
            7 => 'Enseignant',
            8 => 'Parent',
            default => 'Utilisateur',
        };

        $annees = Annee::where('etat', 1)
            ->orderBy('date_rentree', 'desc')
            ->get();

        $stats = [
            'total_users' => User::count(),
            'users_actifs' => User::where('etat', 1)->count(),
            'users_inactifs' => User::where('etat', '!=', 1)->count(),
            'total_roles' => User::distinct('role')->count('role'),
            'role_admin' => User::where('role', 1)->count(),
            'role_directeur' => User::where('role', 2)->count(),
            'role_comptable' => User::where('role', 3)->count(),
            'role_admin_adjoint' => User::where('role', 4)->count(),
            'role_caissier' => User::where('role', 5)->count(),
        ];

        $recentActivities = $this->getRecentActivities();

        return view('admin.tableau', compact(
            'user',
            'anneeCourante',
            'annees',
            'nomComplet',
            'userEmail',
            'roleLabel',
            'stats',
            'recentActivities'
        ));
    }

    /**
     * Affiche le profil de l'utilisateur
     */
    public function profile()
    {
        if (!$this->authService->isLoggedIn()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter.');
        }

        $user = $this->authService->getUser();
        $nomComplet = trim(($user->prenom ?? '') . ' ' . ($user->nom ?? ''));

        $roleLabel = match($user->role ?? 0) {
            1 => 'Administrateur',
            2 => 'Directeur',
            3 => 'Comptable',
            4 => 'Admin Adjoint',
            5 => 'Caissier',
            6 => 'Secrétaire',
            7 => 'Enseignant',
            8 => 'Parent',
            default => 'Utilisateur',
        };

        return view('admin.profile', compact('user', 'nomComplet', 'roleLabel'));
    }

    /**
     * Met à jour le profil de l'utilisateur
     */
    public function updateProfile(Request $request)
    {
        $user = $this->authService->getUser();

        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
        ]);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Met à jour le mot de passe de l'utilisateur
     */
    public function updatePassword(Request $request)
    {
        $user = $this->authService->getUser();

        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->mot_passe)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->mot_passe = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Mot de passe mis à jour avec succès.');
    }

    /**
     * Récupère les activités récentes
     */
    private function getRecentActivities(): array
    {
        return [
            [
                'action' => 'Connexion à la plateforme',
                'user' => 'Administrateur',
                'date' => now()->subMinutes(5)->format('d/m/Y H:i')
            ],
            [
                'action' => 'Mise à jour du profil utilisateur',
                'user' => 'Jean Kouadio',
                'date' => now()->subHours(2)->format('d/m/Y H:i')
            ],
            [
                'action' => 'Création d\'une nouvelle facture',
                'user' => 'Marie Konan',
                'date' => now()->subHours(4)->format('d/m/Y H:i')
            ],
            [
                'action' => 'Paiement enregistré',
                'user' => 'Paul Bamba',
                'date' => now()->subDay()->format('d/m/Y H:i')
            ],
        ];
    }
}
