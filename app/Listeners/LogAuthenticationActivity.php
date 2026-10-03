<?php

namespace App\Listeners;

use App\Domain\Administration\Models\JournalAction;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;

/**
 * Journalise les connexions / déconnexions et met à jour
 * last_login_at / last_login_ip sur users.
 */
class LogAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        // Met à jour la traçabilité utilisateur
        DB::table('users')->where('id', $user->id)->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        $this->journaliser($user, 'login', 'Connexion réussie');
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $this->journaliser($event->user, 'logout', 'Déconnexion');
        }
    }

    protected function journaliser($user, string $action, string $description): void
    {
        try {
            JournalAction::create([
                'user_id'      => $user->id,
                'user_nom'     => trim(($user->nom ?? '') . ' ' . ($user->prenom ?? '')),
                'user_login'   => $user->login,
                'module'       => 'Administration',
                'action'       => $action,
                'description'  => $description,
                'ip'           => request()->ip(),
                'user_agent'   => substr((string) request()->userAgent(), 0, 255),
                'route'        => request()->route()?->getName(),
                'methode_http' => request()->method(),
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Journalisation auth échouée : ' . $e->getMessage());
        }
    }
}