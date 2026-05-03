<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscription_id'      => 'required|exists:inscriptions,id',
            'date_paiement'       => 'required|date',
            'mode_paiement'       => 'required|integer|in:1,2,3,4', // 1=espèces,2=chèque,3=virement,4=mobile money
            'payeur'              => 'nullable|string|max:255',
            'telephone_payeur'    => 'nullable|string|max:50',
            'montant'             => 'required|numeric|min:0',
            'details'             => 'required|array|min:1',
            'details.*.libelle'   => 'required|string|max:255',
            'details.*.montant'   => 'required|numeric|min:0',
            'details.*.type_paiement' => 'required|integer|in:1,2,3,4,5,6,7,8', // à définir: 1=scolarité,2=cantine,3=bus,4=inscription,5=examen,6=activité,7=produit,8=autre
            'details.*.frais_ecole_id' => 'nullable|exists:frais_ecoles,id',
            'details.*.activite_id'    => 'nullable|exists:activites,id',
            'details.*.produit_id'     => 'nullable|exists:produits,id',
        ];
    }

    public function messages()
    {
        return [
            'details.required' => 'Au moins un détail de paiement est requis.',
        ];
    }
}