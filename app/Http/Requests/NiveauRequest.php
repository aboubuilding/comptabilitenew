<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NiveauRequest extends FormRequest
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
        $niveauId = $this->route('niveau') ? $this->route('niveau')->id : null;

        return [
            'libelle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('niveaux', 'libelle')->ignore($niveauId)
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'numero_ordre' => ['nullable', 'integer', 'min:0'],
            'cycle_id' => ['nullable', 'integer', 'exists:cycles,id'],
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
            'description.max' => 'La description ne doit pas dépasser 500 caractères.',
            'numero_ordre.integer' => 'Le numéro d\'ordre doit être un nombre entier.',
            'numero_ordre.min' => 'Le numéro d\'ordre doit être supérieur ou égal à 0.',
            'cycle_id.exists' => 'Le cycle sélectionné est invalide.',
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
            'description' => trim($this->description ?? ''),
            'numero_ordre' => $this->numero_ordre ? (int)$this->numero_ordre : null,
        ]);
    }

    /**
     * Obtenir les données validées avec valeurs par défaut.
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();
        $data['etat'] = $data['etat'] ?? 1;
        $data['numero_ordre'] = $data['numero_ordre'] ?? 0;
        return $data;
    }
}
