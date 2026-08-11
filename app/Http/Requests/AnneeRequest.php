<?php
// app/Http/Requests/AnneeRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnneeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['nullable', 'string', 'max:255'],
            'date_rentree' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_rentree'],
            'date_ouverture_inscription' => ['nullable', 'date'],
            'date_fermeture_reinscription' => ['nullable', 'date', 'after:date_ouverture_inscription'],
            'statut_annee' => ['nullable', 'integer', Rule::in([1, 2, 3])],
            'etat' => ['nullable', 'integer', Rule::in([0, 1])],
        ];
    }

    public function messages(): array
    {
        return [
            'date_fin.after' => 'La date de fin doit être postérieure à la date de rentrée.',
            'date_fermeture_reinscription.after' => 'La date de fermeture des réinscriptions doit être après la date d\'ouverture des inscriptions.',
            'statut_annee.in' => 'Le statut sélectionné est invalide. Valeurs acceptées : 1 (Non ouvert), 2 (Ouvert), 3 (Clôturé).',
            'etat.in' => 'L\'état sélectionné est invalide. Valeurs acceptées : 0 (Inactif), 1 (Actif).',
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
        $data['statut_annee'] = $data['statut_annee'] ?? 1;
        $data['etat'] = $data['etat'] ?? 1;
        return $data;
    }
}
