<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Administration\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * store() répond en JSON, pas par redirection : auth/login.blade.php
 * soumet le formulaire en AJAX et interprète success/code/message/redirect
 * elle-même (bandeau inline + toast selon le code métier renvoyé par
 * AuthService::attempt()).
 */
class LoginController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): JsonResponse
    {
        // Vérifié ici plutôt que via LoginRequest::ensureIsNotRateLimited() :
        // une ValidationException se traduit toujours en 422 côté Laravel,
        // alors que la vue distingue explicitement un 429 ("trop de
        // tentatives") d'un 422 (formulaire invalide), avec des messages
        // différents. Un vrai 429 est nécessaire pour déclencher le bon
        // bandeau côté client.
        if (RateLimiter::tooManyAttempts($request->throttleKey(), 5)) {
            return response()->json([
                'success' => false,
                'code'    => 'TOO_MANY_ATTEMPTS',
                'message' => 'Trop de tentatives de connexion. Veuillez patienter avant de réessayer.',
            ], 429);
        }

        $result = $this->authService->attempt(
            $request->input('login'),
            $request->input('password'),
            $request->boolean('remember')
        );

        if (! $result['success']) {
            RateLimiter::hit($request->throttleKey());

            return response()->json([
                'success' => false,
                'code'    => $result['code'],
                'message' => $result['message'],
            ], 401);
        }

        RateLimiter::clear($request->throttleKey());

        // Régénération de l'ID de session — protection contre la
        // fixation de session (voir le commentaire dans AuthService).
        $request->session()->regenerate();

        return response()->json([
            'success'  => true,
            'code'     => $result['code'],
            'message'  => $result['message'],
            'redirect' => $result['redirect'],
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}