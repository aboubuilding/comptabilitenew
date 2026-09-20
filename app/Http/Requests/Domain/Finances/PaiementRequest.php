<?php

namespace App\Http\Requests\Finances;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Comptable, cf. cahier des charges §4 (2.2)
    }

    public function rules(): array
    {
        return [
            'eleve_id'          => ['required', 'integer', 'exists:eleves,id'],
            'payeur'            => ['required', 'string', 'max:255'],
            'telephone_payeur'  => ['nullable', 'string', 'max:30'],
            'mode_paiement'     => ['required', 'integer', 'in:1,2,3,4'],

            'engagements'                 => ['nullable', 'array'],
            'engagements.*.nature'        => ['required_with:engagements', 'string'],
            'engagements.*.reference_id'  => ['required_with:engagements', 'integer'],
            'engagements.*.montant'       => ['required_with:engagements', 'numeric', 'min:0.01'],

            'panier'            => ['nullable', 'array'],
            'panier.*.nature'   => ['required_with:panier', 'string', 'in:produit,service,evenement'],
            'panier.*.id'       => ['required_with:panier', 'integer'],
            'panier.*.montant'  => ['required_with:panier', 'numeric', 'min:0.01'],
            'panier.*.quantite' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'eleve_id.required' => "L'élève est obligatoire.",
            'payeur.required'   => 'Le nom du payeur est obligatoire.',
        ];
    }

    /**
     * Il faut au moins une ligne (engagement ou panier) — sinon le
     * paiement n'aurait rien à ventiler.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $total = count($this->input('engagements', [])) + count($this->input('panier', []));
            if ($total === 0) {
                $validator->errors()->add('panier', 'Sélectionnez au moins un engagement ou ajoutez un article au panier.');
            }
        });
    }
}