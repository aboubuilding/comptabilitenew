<?php
// app/Http/Middleware/AuthCustom.php

namespace App\Http\Middleware;

use Closure;
use App\Services\AuthService;

class AuthCustom
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle($request, Closure $next)
    {
        if (!$this->authService->isLoggedIn()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }

            return redirect()->route('login')->with('error', 'Veuillez vous connecter.');
        }

        return $next($request);
    }
}
