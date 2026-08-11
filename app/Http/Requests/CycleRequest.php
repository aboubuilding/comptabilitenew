<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CycleRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Les règles de validation.
     */
    public function rules(): array
    {
        $cycleId = $this->route('cycle') ? $this->route('cycle')->id : null;

        return [
            'libelle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cycles', 'libelle')->ignore($cycleId)
            ],
            'etat' => ['nullable', 'integer', Rule::in([0, 1])],
        ];
    }

    /**
     * Messages de validation personnalisés.
     */
    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.unique' => 'Ce libellé est déjà utilisé.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 255 caractères.',
            'etat.in' => 'L\'état sélectionné est invalide.',
        ];
    }

    /**
     * Préparer les données pour la validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'libelle' => trim($this->libelle ?? ''),
        ]);
    }

    /**
     * Obtenir les données validées avec valeurs par défaut.
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();
        $data['etat'] = $data['etat'] ?? 1;
        return $data;
    }
}
