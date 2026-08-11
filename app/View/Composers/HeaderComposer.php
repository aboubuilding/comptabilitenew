<?php
// app/View/Composers/HeaderComposer.php

namespace App\View\Composers;

use App\Services\AuthService;
use App\Models\Annee;
use Illuminate\View\View;

class HeaderComposer
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // Récupérer l'utilisateur connecté
        $user = $this->authService->getUser();

        // Données de l'utilisateur
        $nomComplet = $user ? trim(($user->prenom ?? '') . ' ' . ($user->nom ?? '')) : 'Utilisateur';
        $userEmail = $user->email ?? 'user@ecoleinternationalemariam.net';

        // Rôle label
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

        // Récupérer l'année en cours
        $anneeCourante = $this->authService->getCurrentYear();

        // Récupérer toutes les années actives pour le sélecteur
        $annees = Annee::where('etat', 1)
            ->orderBy('date_rentree', 'desc')
            ->get();

        // Passer les données à la vue
        $view->with([
            'headerUser' => $user,
            'headerNomComplet' => $nomComplet,
            'headerUserEmail' => $userEmail,
            'headerRoleLabel' => $roleLabel,
            'headerAnneeCourante' => $anneeCourante,
            'headerAnnees' => $annees,
        ]);
    }
}
