<?php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Solution transitoire : vérifie le rôle directement ici, en attendant
 * les vraies Policies par module (matrice des 13 profils, pas encore
 * formalisée — voir doc d'architecture). A remplacer par des Policies
 * dès qu'elles existent plutôt que de laisser ce middleware devenir la
 * source de vérité des autorisations sur les 13 modules.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = Auth::user();

        if (! $user || ! $user->role) {
            abort(403, 'Accès non autorisé.');
        }

        // $user->role est une instance de RoleUtilisateur (cast sur le
        // modèle User), pas un entier brut : ->value donne la valeur
        // numérique à comparer aux paramètres du middleware
        // (checkrole:1,2 -> ['1', '2']).
        if (! in_array($user->role->value, array_map('intval', $roles), true)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}