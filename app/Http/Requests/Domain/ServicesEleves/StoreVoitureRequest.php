<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Types\StatutVehicule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoitureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transport.gerer');
    }

    public function rules(): array
    {
        return [
            'marque'            => ['required', 'string', 'max:100'],
            'modele'            => ['nullable', 'string', 'max:100'],
            'plaque'            => ['required', 'string', 'max:30'],
            'nombre_place'      => ['nullable', 'integer', 'min:1', 'max:200'],
            'annee_fabrication' => ['nullable', 'integer', 'between:1950,' . (date('Y') + 1)],
            'numero_chassis'    => ['nullable', 'string', 'max:100'],
            'date_achat'        => ['nullable', 'date'],
            'prix_achat'        => ['nullable', 'numeric', 'min:0'],
            'fournisseur'       => ['nullable', 'string', 'max:150'],
            'kilometrage_actuel'=> ['nullable', 'numeric', 'min:0'],
            'statut'            => ['required', Rule::in(array_keys(StatutVehicule::options()))],
        ];
    }
}