<?php

namespace App\Http\Requests\Finances;

use Illuminate\Foundation\Http\FormRequest;

class PlanEcheancierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Comptable
    }

    public function rules(): array
    {
        return [
            'nom'         => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['nom.required' => 'Le nom du plan est obligatoire.'];
    }
}