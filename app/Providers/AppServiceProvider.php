<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\View\Composers\HeaderComposer;

// ─────────────────────────────────────────────────────────────
// 🔗 Imports des interfaces (dossier Interfaces)
// ─────────────────────────────────────────────────────────────
use App\Repositories\Interfaces\DepenseRepositoryInterface;
use App\Repositories\Interfaces\CaisseRepositoryInterface;
use App\Repositories\Interfaces\MouvementRepositoryInterface;
use App\Repositories\Interfaces\CycleRepositoryInterface;
use App\Repositories\Interfaces\NiveauRepositoryInterface;
use App\Repositories\Interfaces\ClasseRepositoryInterface;
use App\Repositories\Interfaces\AnneeRepositoryInterface;
use App\Repositories\Interfaces\FraisEcoleRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\PlanEcheancierRepositoryInterface;
use App\Repositories\Interfaces\PlanEcheancierLigneRepositoryInterface;
use App\Repositories\Interfaces\EvenementRepositoryInterface;

// ─────────────────────────────────────────────────────────────
// 🔗 Imports des implémentations concrètes (dossier Eloquent)
// ─────────────────────────────────────────────────────────────
use App\Repositories\Eloquent\DepenseRepository;
use App\Repositories\Eloquent\CaisseRepository;
use App\Repositories\Eloquent\MouvementRepository;
use App\Repositories\Eloquent\CycleRepository;
use App\Repositories\Eloquent\NiveauRepository;
use App\Repositories\Eloquent\ClasseRepository;
use App\Repositories\Eloquent\AnneeRepository;
use App\Repositories\Eloquent\FraisEcoleRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\PlanEcheancierRepository;
use App\Repositories\Eloquent\PlanEcheancierLigneRepository;
use App\Repositories\Eloquent\EvenementRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ─────────────────────────────────────────────────────────
        // 💰 MODULE CAISSE
        // ─────────────────────────────────────────────────────────

        $this->app->bind(DepenseRepositoryInterface::class, DepenseRepository::class);
        $this->app->bind(CaisseRepositoryInterface::class, CaisseRepository::class);
        $this->app->bind(MouvementRepositoryInterface::class, MouvementRepository::class);

        // ─────────────────────────────────────────────────────────
        // 📚 MODULE  PARAMETRAGE
        // ─────────────────────────────────────────────────────────

        $this->app->bind(CycleRepositoryInterface::class, CycleRepository::class);
        $this->app->bind(NiveauRepositoryInterface::class, NiveauRepository::class);
        $this->app->bind(ClasseRepositoryInterface::class, ClasseRepository::class);
        $this->app->bind(AnneeRepositoryInterface::class, AnneeRepository::class);
        $this->app->bind(FraisEcoleRepositoryInterface::class, FraisEcoleRepository::class);
        $this->app->bind(PeriodeRepositoryInterface::class, PeriodeRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(PlanEcheancierRepositoryInterface::class, PlanEcheancierRepository::class);
        $this->app->bind(PlanEcheancierLigneRepositoryInterface::class, PlanEcheancierLigneRepository::class);
        $this->app->bind(EvenementRepositoryInterface::class, EvenementRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Exemple : Partager une variable globale avec toutes les vues
        // view()->share('app_name', config('app.name'));
        // Enregistrer le composer pour le header
        View::composer('admin.layouts.partials._header', HeaderComposer::class);
    }
}
