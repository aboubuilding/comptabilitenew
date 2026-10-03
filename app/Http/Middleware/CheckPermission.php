<?php
// app/Http/Middleware/CheckPermission.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôle d'accès des Controllers : permission:caisses.voir,
 * permission:inscriptions.saisir,inscriptions.valider (l'une OU l'autre).
 *
 * La décision appartient au Gate, donc à la matrice du cahier des charges
 * (config/permissions.php) : ce middleware, les FormRequest et les @can des
 * vues répondent ainsi toujours la même chose.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Accès non autorisé.');
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Accès non autorisé.');
    }
}
