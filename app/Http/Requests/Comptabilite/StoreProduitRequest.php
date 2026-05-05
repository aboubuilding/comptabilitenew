<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // À adapter selon les permissions
    }

    public function rules(): array
    {
        return [
            'libelle'        => 'required|string|max:255|unique:produits,libelle',
            'prix_unitaire'  => 'required|numeric|min:0',
            'photo'          => 'nullable|string|max:255',
            'unite_stock'    => 'nullable|string|max:50',
            'unite_achat'    => 'nullable|string|max:50',
            'equivalence'    => 'nullable|numeric|min:1',
            'type_produit'   => 'nullable|integer|min:1|max:10',
            'quantite_stock' => 'nullable|integer|min:0',
            'seuil_alerte'   => 'nullable|integer|min:0',
            'stock_min'      => 'nullable|integer|min:0',
            'stock_max'      => 'nullable|integer|min:0|gte:stock_min',
            'etat'           => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'libelle.unique' => 'Ce produit existe déjà.',
            'prix_unitaire.min' => 'Le prix unitaire doit être positif ou nul.',
            'stock_max.gte' => 'Le stock maximum doit être supérieur ou égal au stock minimum.',
        ];
    }
}