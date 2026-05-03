<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFraisEcoleRequest extends FormRequest
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
            'type_paiement'  => 'required|integer|in:1,2,3',
            'type_forfait'   => 'required|integer|in:1,2',
            'niveau_id'      => 'required|integer|exists:niveaux,id',
            'etat'           => 'sometimes|integer|in:0,1',
        ];
    }
}