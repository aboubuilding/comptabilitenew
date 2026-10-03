<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Les permissions (« module.action ») ne sont pas des Gates déclarés un
        // à un : elles sont résolues par la matrice profil -> permissions du
        // cahier des charges (config/permissions.php). Le middleware
        // `permission:`, les FormRequest ($user->can(...)) et les @can des vues
        // passent tous par ici.
        Gate::before(function ($user, string $ability) {
            if (! $user instanceof \App\Domain\Administration\Models\User || ! str_contains($ability, '.')) {
                return null; // ce n'est pas une permission de la matrice : policies habituelles
            }

            return $user->isActive() && app(Permissions::class)->autoriseUnDesProfils($user->profils(), $ability);
        });
    }
}
