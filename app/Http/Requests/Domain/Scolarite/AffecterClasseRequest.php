<?php

namespace App\Http\Requests\Domain\Scolarite;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest pour l'affectation / désaffectation de classe.
 * classe_id = null  →  désaffectation.
 */
class AffecterClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id'   => ['nullable', 'integer', Rule::exists('classes', 'id')->where('etat', 1)],
            'eleve_ids'   => ['required', 'array', 'min:1'],
            'eleve_ids.*' => ['integer', Rule::exists('eleves', 'id')->where('etat', 1)],
        ];
    }

    public function messages(): array
    {
        return [
            'classe_id.exists'   => "La classe sélectionnée est introuvable ou inactive.",
            'eleve_ids.required' => "Aucun élève sélectionné.",
            'eleve_ids.min'      => "Sélectionnez au moins un élève.",
            'eleve_ids.*.exists' => "Un des élèves sélectionnés est introuvable ou inactif.",
        ];
    }
}