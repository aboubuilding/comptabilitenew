<?php

namespace App\Http\Requests\Domain\Rh;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest unique pour la création ET la modification
 * d'une souscription d'assurance du personnel.
 */
class StoreAssureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employe_id' => [
                'required',
                'integer',
                Rule::exists('employes', 'id')->where('etat', 1),
            ],
            'assurance_id' => [
                'required',
                'integer',
                Rule::exists('asurances', 'id')->where('etat', 1),
            ],
            'date_souscription' => ['required', 'date'],
            'prelevement_mensuel' => ['required', 'numeric', 'min:0'],
            'statut' => ['nullable', Rule::in(['actif', 'suspendu', 'termine'])],
        ];
    }

    public function messages(): array
    {
        return [
            'employe_id.required'          => "L'employé est obligatoire.",
            'employe_id.exists'            => "L'employé sélectionné est introuvable ou inactif.",
            'assurance_id.required'        => "L'assurance est obligatoire.",
            'assurance_id.exists'          => "L'assurance sélectionnée est introuvable ou inactive.",
            'date_souscription.required'   => "La date de souscription est obligatoire.",
            'date_souscription.date'       => "La date de souscription est invalide.",
            'prelevement_mensuel.required' => "Le prélèvement mensuel est obligatoire.",
            'prelevement_mensuel.numeric'  => "Le prélèvement mensuel doit être un nombre.",
            'prelevement_mensuel.min'      => "Le prélèvement mensuel ne peut pas être négatif.",
            'statut.in'                    => "Le statut sélectionné est invalide.",
        ];
    }
}