<?php

namespace App\Http\Requests\Domain\Logistique;

use App\Domain\Logistique\Types\AchatType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('achats.gerer');
    }

    public function rules(): array
    {
        return [
            'date_achat'      => ['required', 'date'],
            'nom_acheteur'    => ['nullable', 'string', 'max:150'],
            'bon_commande'    => ['nullable', 'string', 'max:150'],
            'commentaire'     => ['nullable', 'string', 'max:1000'],
            'fournisseur_id'  => ['required', 'exists:fournisseurs,id'],
            'type_achat'      => ['required', Rule::in(array_keys(AchatType::options()))],

            'lignes'                  => ['required', 'array', 'min:1'],
            'lignes.*.produit_id'     => ['required', 'integer', 'min:1'],
            'lignes.*.quantite'       => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire'  => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'lignes.required' => 'Ajoutez au moins une ligne à l\'achat.',
            'lignes.min'      => 'Ajoutez au moins une ligne à l\'achat.',
        ];
    }
}