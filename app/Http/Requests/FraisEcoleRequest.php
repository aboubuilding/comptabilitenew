<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FraisEcoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fraisId = $this->route('frais_ecole') ? $this->route('frais_ecole')->id : null;

        return [
            'libelle' => ['required', 'string', 'max:255', Rule::unique('frais_ecoles')->ignore($fraisId)],
            'montant' => ['nullable', 'numeric', 'min:0'],
            'type_paiement' => ['required', 'integer', Rule::in([1,2,3,4,5,6,7,8,9,10,11,12])],
            'type_forfait' => ['nullable', 'integer', Rule::in([1,2,3])],
            'niveau_id' => ['nullable', 'integer', 'exists:niveaux,id'],
            'annee_id' => ['nullable', 'integer', 'exists:annees,id'],
            'plan_echeancier_id' => ['nullable', 'integer', 'exists:plan_echeanciers,id'],
            'etat' => ['nullable', 'integer', Rule::in([0,1])],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.unique' => 'Ce libellé est déjà utilisé.',
            'type_paiement.required' => 'Le type de paiement est obligatoire.',
            'type_paiement.in' => 'Le type de paiement sélectionné est invalide.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être supérieur ou égal à 0.',
            'niveau_id.exists' => 'Le niveau sélectionné est invalide.',
            'annee_id.exists' => 'L\'année sélectionnée est invalide.',
            'plan_echeancier_id.exists' => 'Le plan d\'échéancier sélectionné est invalide.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'libelle' => trim($this->libelle ?? ''),
        ]);
    }

    public function validatedWithDefaults(): array
    {
        $data = $this->validated();
        $data['etat'] = $data['etat'] ?? 1;
        return $data;
    }
}
