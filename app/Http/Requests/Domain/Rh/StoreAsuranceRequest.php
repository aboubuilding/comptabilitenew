<?php

namespace App\Http\Requests\Domain\Rh;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest unique pour la création ET la modification
 * d'une assurance du catalogue (assureur / police).
 */
class StoreAsuranceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $asuranceId = $this->route('asurance')?->id;

        return [
            'nom_assureur'      => ['required', 'string', 'max:255'],
            'police_assurance'  => [
                'required',
                'string',
                'max:255',
                Rule::unique('asurances', 'police_assurance')
                    ->ignore($asuranceId)
                    ->where(fn ($q) => $q->where('etat', 1)),
            ],
            'montant_annuel'    => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_assureur.required'     => "Le nom de l'assureur est obligatoire.",
            'nom_assureur.max'          => "Le nom de l'assureur est trop long.",
            'police_assurance.required' => "Le numéro de police est obligatoire.",
            'police_assurance.unique'   => "Ce numéro de police existe déjà pour une assurance active.",
            'police_assurance.max'      => "Le numéro de police est trop long.",
            'montant_annuel.numeric'    => "Le montant annuel doit être un nombre.",
            'montant_annuel.min'        => "Le montant annuel ne peut pas être négatif.",
        ];
    }
}