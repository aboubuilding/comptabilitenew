<?php

namespace App\Http\Requests\Finances;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerEncaissementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Caissier, avant clôture de caisse
    }

    public function rules(): array
    {
        return ['motif' => ['required', 'string', 'min:5']];
    }

    public function messages(): array
    {
        return [
            'motif.required' => "Le motif d'annulation est obligatoire.",
            'motif.min'      => 'Le motif doit être un peu plus détaillé (5 caractères minimum).',
        ];
    }
}