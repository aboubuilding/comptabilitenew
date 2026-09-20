<?php

namespace App\Http\Requests\Finances;

use Illuminate\Foundation\Http\FormRequest;

class FraisEcoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Comptable, cf. cahier des charges §4 (2.1)
    }

    public function rules(): array
    {
        return [
            'libelle'             => ['required', 'string', 'max:255'],
            'montant'             => ['required', 'numeric', 'min:0'],
            'type_paiement'       => ['required', 'integer', 'in:1,2'],
            'type_forfait'        => ['required', 'integer', 'in:1,2,3,4,5,6,7'],
            'niveau_id'           => ['required', 'integer', 'exists:niveaux,id'],
            'plan_echeancier_id'  => ['nullable', 'integer', 'exists:plan_echeanciers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'       => 'Le libellé du frais est obligatoire.',
            'montant.required'       => 'Le montant est obligatoire.',
            'type_paiement.required' => 'Le type de paiement est obligatoire.',
            'type_forfait.required'  => 'La nature du frais est obligatoire.',
            'niveau_id.required'     => 'Le niveau concerné est obligatoire.',
        ];
    }

    public function attributes(): array
    {
        return [
            'niveau_id'          => 'niveau',
            'type_paiement'      => 'type de paiement',
            'type_forfait'       => 'nature du frais',
            'plan_echeancier_id' => "plan d'échéancier",
        ];
    }
}