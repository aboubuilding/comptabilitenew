<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjustementInventaireRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'produit_id'   => 'required|exists:produits,id',
            'magasin_id'   => 'required|exists:magasins,id',
            'quantite_reelle' => 'required|numeric|min:0',
            'motif'        => 'required|string|max:255',
        ];
    }
}