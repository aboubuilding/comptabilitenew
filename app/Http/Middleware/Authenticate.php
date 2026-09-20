<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * route('login'), pas route('admin_login') — le nom réel défini dans
     * routes/auth.php. L'ancien nom n'existe pas : un visiteur non
     * connecté touchant une route protégée aurait fait planter CE
     * middleware avec une RouteNotFoundException, indépendamment du
     * problème AuthService.
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}