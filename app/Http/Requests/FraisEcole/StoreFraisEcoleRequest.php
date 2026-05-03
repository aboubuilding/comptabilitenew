<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFraisEcoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'        => 'required|string|max:255',
            'montant'        => 'required|numeric|min:0',
            'type_paiement'  => 'required|integer|in:1,2,3',      // Ex: 1=unique, 2=trimestriel, 3=mensuel
            'type_forfait'   => 'required|integer|in:1,2',        // Ex: 1=obligatoire, 2=optionnel
            'niveau_id'      => 'required|integer|exists:niveaux,id',
            'etat'           => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'niveau_id.exists' => 'Le niveau sélectionné est invalide.',
            'montant.min'      => 'Le montant doit être supérieur ou égal à 0.',
        ];
    }
}