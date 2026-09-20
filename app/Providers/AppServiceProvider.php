<?php

namespace App\Providers;

use App\Support\AnneeScolaireContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Domain\Finances\Models\Banque;
use App\Domain\Finances\Models\Cheque;
use App\Policies\Domain\Finances\BanquePolicy;
use App\Policies\Domain\Finances\ChequePolicy;

/**
 * Pas de binding Repository ici : aucun de nos Repository de domaine
 * (NiveauRepository, CycleRepository, UserRepository, AnneeRepository...)
 * n'a d'interface séparée — décision actée (voir doc d'architecture) —
 * et Laravel résout tout seul un type-hint sur une classe concrète dont
 * le constructeur n'attend qu'un Model Eloquent (auto-wiring par
 * réflexion, rien à déclarer explicitement ici).
 *
 * Le seul binding nécessaire est le singleton du contexte année
 * scolaire, partagé entre le middleware ResolveAnneeScolaire (qui le
 * renseigne) et tout ce qui le consomme ensuite pendant la même requête
 * (BaseRepository, HeaderComposer, TableauController...).
 *
 * L'enregistrement du HeaderComposer NE VIT PAS ICI : voir
 * ViewComposerServiceProvider, dédié à ça — le dupliquer dans les deux
 * fichiers l'aurait fait s'enregistrer deux fois pour rien.
 */
class AppServiceProvider extends ServiceProvider
{

protected $policies = [
    Banque::class => BanquePolicy::class,
    Cheque::class => ChequePolicy::class,
];
    public function register(): void
    {
        $this->app->singleton(AnneeScolaireContext::class);
    }

    public function boot(): void
    {
        // S'applique automatiquement à chaque `->links()` des 13
        // modules — rien à changer dans les vues qui l'utilisent déjà.
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');
    }
}