<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanEcheancierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->route('plan_echeancier') ? $this->route('plan_echeancier')->id : null;

        return [
            'nom' => ['required', 'string', 'max:255', Rule::unique('plan_echeanciers')->ignore($planId)],
            'description' => ['nullable', 'string', 'max:500'],
            'annee_id' => ['nullable', 'integer', 'exists:annees,id'],
            'etat' => ['nullable', 'integer', Rule::in([0,1])],
            'lignes' => ['nullable', 'array'],
            'lignes.*.ordre' => ['required_with:lignes', 'integer', 'min:1'],
            'lignes.*.jour_echeance' => ['nullable', 'integer', 'min:1', 'max:31'],
            'lignes.*.date_echeance' => ['nullable', 'date'],
            'lignes.*.montant' => ['nullable', 'numeric', 'min:0'],
            'lignes.*.pourcentage' => ['nullable', 'numeric', 'between:0,100'],
            'lignes.*.libelle' => ['required_with:lignes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.unique' => 'Ce nom est déjà utilisé.',
            'lignes.*.ordre.required_with' => 'L\'ordre est obligatoire pour chaque ligne.',
            'lignes.*.libelle.required_with' => 'Le libellé est obligatoire pour chaque ligne.',
            'lignes.*.montant.numeric' => 'Le montant doit être un nombre.',
            'lignes.*.pourcentage.between' => 'Le pourcentage doit être entre 0 et 100.',
            'lignes.*.jour_echeance.min' => 'Le jour d\'échéance doit être au minimum 1.',
            'lignes.*.jour_echeance.max' => 'Le jour d\'échéance doit être au maximum 31.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nom' => trim($this->nom ?? ''),
            'description' => trim($this->description ?? ''),
        ]);
    }

    public function validatedWithDefaults(): array
    {
        $data = $this->validated();
        $data['etat'] = $data['etat'] ?? 1;
        return $data;
    }
}
