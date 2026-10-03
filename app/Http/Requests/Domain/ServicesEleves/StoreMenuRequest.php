<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Types\TypeRepas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cantine.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'         => ['required', 'string', 'max:200'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'date_service'    => ['required', 'date'],
            'type_repas'      => ['required', Rule::in(array_keys(TypeRepas::options()))],
            'quantite_prevue' => ['required', 'integer', 'min:0'],
            'magasin_id'      => ['required', 'exists:magasins,id'],

            'produits'                 => ['required', 'array', 'min:1'],
            'produits.*.produit_id'    => ['required', 'exists:produits,id'],
            'produits.*.quantite'      => ['required', 'numeric', 'min:0.001'],
            'produits.*.cout_unitaire' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'produits.required' => 'Un menu doit comporter au moins un produit.',
        ];
    }
}