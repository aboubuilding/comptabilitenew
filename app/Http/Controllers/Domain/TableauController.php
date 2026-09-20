<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Administration\Repositories\UserRepository;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Scolarite\Repositories\AnneeRepository;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Tableau de bord — hors des 6 domaines métier (Scolarite, Finances...) :
 * il agrège des données de plusieurs d'entre eux, donc pas de "domaine
 * propriétaire" unique à lui assigner.
 *
 * Volontairement allégé pour l'instant : seules les statistiques déjà
 * disponibles via UserRepository/AnneeRepository sont affichées. A
 * étoffer au fur et à mesure que Finances/Scolarité/etc. existent
 * réellement. Si l'agrégation devient complexe (KPI croisant plusieurs
 * domaines), elle mérite alors un vrai DashboardService plutôt que de
 * rester ici.
 */
class TableauController extends Controller
{
    public function __construct(
        private UserRepository $users,
        private AnneeRepository $annees,
        private AnneeScolaireContext $anneeContext,
    ) {
    }

    /**
     * Pas de vérification d'authentification ici : le middleware 'auth'
     * (routes/web.php) garantit déjà qu'on n'atteint jamais cette méthode
     * sans utilisateur connecté — la revérifier serait redondant.
     */
    public function tableau(): View
    {
        $user = Auth::user();

        $anneeActive = $this->anneeContext->id()
            ? $this->annees->find($this->anneeContext->id())
            : null;

        return view('admin.tableau', [
            'user'             => $user,
            'nomComplet'       => $user->full_name,
            'roleLabel'        => $user->role_label,
            'anneeActive'      => $anneeActive,
            'stats'            => $this->buildStats(),
            'recentActivities' => $this->getRecentActivities(),
        ]);
    }

    /**
     * Composé directement ici pour l'instant (deux appels de Repository,
     * rien de plus) — à extraire dans un DashboardService dédié si
     * d'autres domaines viennent s'y ajouter.
     */
    private function buildStats(): array
    {
        return [
            'total_users'    => $this->users->withInactifs()->count(),
            'users_actifs'   => $this->users->count(),
            'users_inactifs' => $this->users->onlyInactifs()->count(),
            'total_roles'    => $this->users->activeQuery()->pluck('role')->filter()->unique()->count(),
            'role_admin'     => $this->users->getByRole(RoleUtilisateur::Administrateur)->count(),
            'role_directeur' => $this->users->getByRole(RoleUtilisateur::Directeur)->count(),
        ];
    }

    /**
     * TODO : brancher sur un vrai journal d'activités ("Journal &
     * Traçabilité", pas encore construit) une fois qu'il existe. Un
     * tableau vide plutôt que des données inventées — la vue gère déjà
     * proprement ce cas ("Aucune activité récente").
     */
    private function getRecentActivities(): array
    {
        return [];
    }
}