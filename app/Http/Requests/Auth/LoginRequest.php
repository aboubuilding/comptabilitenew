<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route publique, pas de vérification de permission ici
    }

    public function rules(): array
    {
        return [
            'login'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required'    => "L'identifiant est requis.",
            'password.required' => 'Le mot de passe est requis.',
        ];
    }

    /**
     * Clé de limitation par IP + identifiant. Le contrôle du nombre de
     * tentatives (RateLimiter::tooManyAttempts) se fait dans
     * LoginController::store(), pas ici — voir le commentaire du
     * contrôleur : une exception de validation se traduit toujours en
     * 422, alors que le flux JSON de la vue attend un vrai 429 pour ce
     * cas précis.
     */
    public function throttleKey(): string
    {
        return Str::lower($this->input('login', '')) . '|' . $this->ip();
    }
}