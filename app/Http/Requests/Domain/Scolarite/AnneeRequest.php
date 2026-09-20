<?php

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnneeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Administrateur seul, cf. cahier des charges §4 (1.4)
    }

    public function rules(): array
    {
        return [
            'libelle' => [
                'required', 'string', 'max:255',
                Rule::unique('annees', 'libelle')->ignore($this->route('annee')),
            ],
            'date_rentree' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_rentree'],
            'date_ouverture_inscription' => ['nullable', 'date'],
            'date_fermeture_reinscription' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'      => "Le libellé de l'année scolaire est obligatoire.",
            'libelle.unique'        => 'Cette année scolaire existe déjà.',
            'date_rentree.required' => 'La date de rentrée est obligatoire.',
            'date_fin.required'     => 'La date de fin est obligatoire.',
            'date_fin.after'        => 'La date de fin doit être postérieure à la date de rentrée.',
        ];
    }

    public function attributes(): array
    {
        return [
            'libelle'                      => 'libellé',
            'date_rentree'                 => 'date de rentrée',
            'date_fin'                     => 'date de fin',
            'date_ouverture_inscription'   => "date d'ouverture des inscriptions",
            'date_fermeture_reinscription' => 'date de fermeture des réinscriptions',
        ];
    }
}