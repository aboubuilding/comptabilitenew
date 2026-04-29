<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejeterDepenseRequest extends FormRequest
{
    /**
     * Autoriser la requête (à adapter si tu veux restreindre par rôle)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation adaptées au champ `motif_rejet`
     */
    public function rules(): array
    {
        return [
            'motif_rejet' => 'required|string|min:10|max:500',
        ];
    }

    /**
     * Messages d'erreur simples et explicites
     */
    public function messages(): array
    {
        return [
            'motif_rejet.required' => 'Veuillez indiquer la raison du rejet.',
            'motif_rejet.min'      => 'Le motif doit contenir au moins 10 caractères.',
            'motif_rejet.max'      => 'Le motif ne doit pas dépasser 500 caractères.',
        ];
    }
}