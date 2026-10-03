<?php

namespace App\Http\Requests\Domain\Logistique;

use App\Domain\Logistique\Types\TypeBon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.gerer');
    }

    public function rules(): array
    {
        return [
            'type'                   => ['required', Rule::in(array_keys(TypeBon::options()))],
            'date_bon'               => ['required', 'date'],
            'magasin_id'             => ['required', 'exists:magasins,id'],
            'magasin_destination_id' => ['required_if:type,' . TypeBon::TRANSFERT->value, 'nullable', 'exists:magasins,id'],
            'nom_responsable'        => ['nullable', 'string', 'max:150'],
            'nom_magasinier'         => ['nullable', 'string', 'max:150'],
            'motif'                  => ['nullable', 'string', 'max:200'],
            'commentaire'            => ['nullable', 'string', 'max:1000'],

            'lignes'                  => ['required', 'array', 'min:1'],
            'lignes.*.produit_id'     => ['required', 'exists:produits,id'],
            'lignes.*.quantite'       => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire'  => ['nullable', 'numeric', 'min:0'],
        ];
    }
}