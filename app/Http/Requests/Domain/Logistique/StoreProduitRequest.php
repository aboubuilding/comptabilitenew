<?php

namespace App\Http\Requests\Domain\Logistique;

use App\Domain\Logistique\Types\TypeProduit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.gerer');
    }

    public function rules(): array
    {
        $id = $this->route('produit');

        return [
            'libelle'       => ['required', 'string', 'max:200'],
            'prix_unitaire' => ['required', 'numeric', 'min:0'],
            'unite_stock'   => ['required', 'string', 'max:20'],
            'unite_achat'   => ['nullable', 'string', 'max:20'],
            'equivalence'   => ['nullable', 'numeric', 'min:0.0001'],
            'type_produit'  => ['required', Rule::in(array_keys(TypeProduit::options()))],
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}