<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProduitRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code'             => 'nullable|string|max:50|unique:produits,code',
            'libelle'          => 'required|string|max:255',
            'categorie'        => 'nullable|string|max:100',
            'description'      => 'nullable|string',
            'photo'            => 'nullable|string|max:255',
            'type_produit'     => 'nullable|integer|min:1|max:10',
            'unite_base'       => 'required|string|max:20',
            'unite_achat'      => 'nullable|string|max:20',
            'conversion_achat' => 'nullable|numeric|min:0.001',
            'unite_vente'      => 'nullable|string|max:20',
            'conversion_vente' => 'nullable|numeric|min:0.001',
            'prix_achat'       => 'nullable|numeric|min:0',
            'prix_vente'       => 'required|numeric|min:0',
            'quantite_stock'   => 'nullable|numeric|min:0',
            'seuil_alerte'     => 'nullable|numeric|min:0',
            'stock_min'        => 'nullable|numeric|min:0',
            'stock_max'        => 'nullable|numeric|min:0|gte:stock_min',
            'etat'             => 'sometimes|integer|in:0,1',
        ];
    }
}