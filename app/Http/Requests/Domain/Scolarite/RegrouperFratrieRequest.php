<?php

namespace App\Http\Requests\Domain\Scolarite;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegrouperFratrieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode'         => ['required', Rule::in(['nouveau', 'existant'])],
            'nom_famille'  => ['required_if:mode,nouveau', 'nullable', 'string', 'max:150'],
            'espace_id'    => [
                'required_if:mode,existant',
                'nullable',
                'integer',
                Rule::exists('espaces', 'id')->where('etat', 1),
            ],
            'eleve_ids'    => ['required', 'array', 'min:1'],
            'eleve_ids.*'  => ['integer', Rule::exists('eleves', 'id')->where('etat', 1)],
        ];
    }

    public function messages(): array
    {
        return [
            'mode.required'          => "Le mode de regroupement est obligatoire.",
            'mode.in'                => "Le mode sélectionné est invalide.",
            'nom_famille.required_if'=> "Le nom de la famille est obligatoire pour créer un nouvel espace.",
            'espace_id.required_if'  => "L'espace familial est obligatoire.",
            'eleve_ids.required'     => "Aucun élève sélectionné.",
            'eleve_ids.min'          => "Sélectionnez au moins un élève.",
            'eleve_ids.*.exists'     => "Un des élèves sélectionnés est introuvable.",
        ];
    }
}