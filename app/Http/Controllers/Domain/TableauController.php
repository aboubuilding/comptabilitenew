<?php

namespace App\Http\Controllers\Domain;

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

        return view('domain.tableau', [
            'user'             => $user,
            'nomComplet'       => $user->full_name,
            'roleLabel'        => $user->role_label,
            'anneeActive'      => $anneeActive,
            'stats'            => $user->can('utilisateurs.voir') ? $this->buildStats() : [],
            'recentActivities' => $user->can('journal.voir') ? $this->getRecentActivities() : [],
            'acces'            => $this->accesRapides($user),
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
            'activites_7j'   => \App\Domain\Administration\Models\JournalAction::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /**
     * Écrans du menu (config/menu.php) accessibles à l'utilisateur, par menu :
     * mêmes règles que la barre de navigation, donc aucun lien interdit.
     */
    private function accesRapides($user): \Illuminate\Support\Collection
    {
        return collect(config('menu', []))
            ->map(fn (array $menu) => [
                'libelle' => $menu['libelle'],
                'icone'   => $menu['icone'],
                'entrees' => collect($menu['groupes'])
                    ->flatten(1)
                    ->filter(fn (array $entree) => \Illuminate\Support\Facades\Route::has($entree[2]) && $user->can($entree[3]))
                    ->take(6)
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $menu) => $menu['entrees'] !== [])
            ->values();
    }

    /** Dix dernières lignes du journal (§ 6.4 Journal & Traçabilité). */
    private function getRecentActivities(): array
    {
        return \App\Domain\Administration\Models\JournalAction::query()
            ->latest('created_at')
            ->limit(10)
            ->get(['description', 'action', 'entite', 'user_nom', 'user_login', 'created_at'])
            ->map(fn ($ligne) => [
                'action' => $ligne->description ?: trim("{$ligne->action} {$ligne->entite}"),
                'user'   => $ligne->user_nom ?: ($ligne->user_login ?: '—'),
                'date'   => optional($ligne->created_at)->format('d/m/Y H:i'),
            ])
            ->all();
    }
}