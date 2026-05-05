<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'libelle'        => ['required', 'string', 'max:255', Rule::unique('produits', 'libelle')->ignore($id)],
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
}