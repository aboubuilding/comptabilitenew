<?php

namespace App\Http\Requests\Domain\Logistique;

use App\Domain\Logistique\Types\TypeVente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ventes.creer');
    }

    /**
     * Une case décochée n'est pas envoyée par le navigateur : son absence
     * signifie « vente non payée », pas « valeur par défaut ».
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['marquer_payee' => $this->boolean('marquer_payee')]);
    }

    public function rules(): array
    {
        return [
            'date_vente'  => ['required', 'date'],
            'magasin_id'  => ['required', 'exists:magasins,id'],
            'type_vente'  => ['required', Rule::in(array_keys(TypeVente::options()))],

            'client_id'      => ['nullable', 'exists:clients,id'],
            'inscription_id' => ['nullable', 'exists:inscriptions,id'],

            'lignes'                 => ['required', 'array', 'min:1'],
            'lignes.*.produit_id'    => ['required', 'exists:produits,id'],
            'lignes.*.quantite'      => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
            'lignes.*.remise'        => ['nullable', 'numeric', 'min:0', 'max:100'],

            'marquer_payee' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'lignes.required' => 'Ajoutez au moins un produit à la vente.',
            'lignes.min'      => 'Ajoutez au moins un produit à la vente.',
        ];
    }
}