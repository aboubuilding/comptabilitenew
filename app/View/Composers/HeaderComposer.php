<?php

namespace App\View\Composers;

use App\Domain\Scolarite\Repositories\AnneeRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Alimente le header (identité/rôle de l'utilisateur, sélecteur d'année).
 *
 * Ne décide JAMAIS de l'année active : se contente de lire l'état déjà
 * résolu par le middleware ResolveAnneeScolaire (via AnneeScolaireContext)
 * pour cette requête. Le changement d'année lui-même se produit dans le
 * middleware, pas ici.
 *
 * Pas de dépendance à AuthService : Auth::user() (guard natif Laravel)
 * suffit pour un simple accès en lecture à l'utilisateur déjà authentifié
 * — passer par un Service ici aurait été une indirection inutile.
 */
class HeaderComposer
{
    public function __construct(
        private AnneeRepository $annees,
        private AnneeScolaireContext $anneeContext,
    ) {
    }

    public function compose(View $view): void
    {
        $user = Auth::user();

        $anneeCourante = $this->anneeContext->id()
            ? $this->annees->find($this->anneeContext->id())
            : null;

        $view->with([
            'headerUser'          => $user,
            'headerNomComplet'    => $user?->full_name ?? 'Utilisateur',
            'headerUserEmail'     => $user?->email ?? 'user@ecoleinternationalemariam.net',
            // Délègue à l'accesseur du modèle (role_label -> RoleUtilisateur::label()),
            // plutôt qu'un match() codé en dur ici : c'était le bug de la
            // version précédente, désynchronisée dès qu'un rôle change.
            'headerRoleLabel'     => $user?->role_label ?? 'Utilisateur',
            'headerAnneeCourante' => $anneeCourante,
            'headerAnnees'        => $this->annees->allForSelector(),
        ]);
    }
}