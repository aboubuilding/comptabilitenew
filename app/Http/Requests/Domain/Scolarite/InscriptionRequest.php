<?php

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;

class InscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Secrétariat (saisie), cf. cahier des charges §4 (1.3)
    }

    public function rules(): array
    {
        return [
            'eleve_id'          => ['required', 'integer', 'exists:eleves,id'],
            'cycle_id'          => ['required', 'integer', 'exists:cycles,id'],
            'niveau_id'         => ['required', 'integer', 'exists:niveaux,id'],
            'classe_id'         => ['nullable', 'integer', 'exists:classes,id'],
            'type_inscription'  => ['required', 'integer', 'in:1,2'],
            'date_inscription'  => ['required', 'date'],
            'taux_remise'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'frais_scolarite'   => ['nullable', 'numeric', 'min:0'],
            'frais_inscription' => ['nullable', 'numeric', 'min:0'],
            'frais_assurance'   => ['nullable', 'numeric', 'min:0'],
            'frais_examen'      => ['nullable', 'numeric', 'min:0'],
            'frais_cantine'     => ['nullable', 'numeric', 'min:0'],
            'frais_bus'         => ['nullable', 'numeric', 'min:0'],
            'frais_livre'       => ['nullable', 'numeric', 'min:0'],
            'remise_scolarite'  => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'eleve_id.required'  => "L'élève est obligatoire.",
            'cycle_id.required'  => 'Le cycle est obligatoire.',
            'niveau_id.required' => 'Le niveau est obligatoire.',
            'type_inscription.required' => "Le type d'inscription est obligatoire.",
            'date_inscription.required' => "La date d'inscription est obligatoire.",
        ];
    }

    public function attributes(): array
    {
        return [
            'eleve_id' => 'élève', 'cycle_id' => 'cycle', 'niveau_id' => 'niveau', 'classe_id' => 'classe',
            'type_inscription' => "type d'inscription", 'date_inscription' => "date d'inscription",
            'taux_remise' => 'taux de remise',
        ];
    }
}