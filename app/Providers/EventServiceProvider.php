<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

         \Illuminate\Auth\Events\Login::class  => [
        \App\Listeners\LogAuthenticationActivity::class . '@handleLogin',
    ],
    \Illuminate\Auth\Events\Logout::class => [
        \App\Listeners\LogAuthenticationActivity::class . '@handleLogout',
    ],

    ];

    protected $observers = [
    \App\Domain\Finances\Models\Depense::class => \App\Domain\Finances\Observers\MouvementObserver::class,
    \App\Domain\Finances\Models\Detail::class  => \App\Domain\Finances\Observers\DetailEvenementObserver::class,
];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
