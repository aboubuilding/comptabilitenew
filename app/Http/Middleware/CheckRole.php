<?php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use App\Services\AuthService;

class CheckRole
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle($request, Closure $next, ...$roles)
    {
        $user = $this->authService->getUser();

        if (!$user || !in_array($user->role, array_map('intval', $roles))) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
