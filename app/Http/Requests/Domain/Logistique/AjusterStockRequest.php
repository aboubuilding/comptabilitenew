<?php

namespace App\Http\Requests\Domain\Logistique;

use Illuminate\Foundation\Http\FormRequest;

class AjusterStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.ajuster');
    }

    public function rules(): array
    {
        return [
            'produit_id'        => ['required', 'exists:produits,id'],
            'magasin_id'        => ['required', 'exists:magasins,id'],
            'nouvelle_quantite' => ['required', 'numeric', 'min:0'],
            'motif'             => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}