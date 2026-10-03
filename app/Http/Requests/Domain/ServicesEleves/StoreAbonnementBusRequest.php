<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Types\SensTrajet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbonnementBusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transport.abonner');
    }

    public function rules(): array
    {
        return [
            'inscription_id'  => ['required', 'exists:inscriptions,id'],
            'date_debut'      => ['required', 'date'],
            'montant_mensuel' => ['required', 'numeric', 'min:0.01'],
            'nombre_mois'     => ['required', 'integer', 'min:1', 'max:12'],
            'zone_id'         => ['nullable', 'exists:zones,id'],
            'voiture_id'      => ['nullable', 'exists:voitures,id'],
            'ligne_id'        => ['nullable', 'exists:lignes,id'],
            'sens'            => ['required', Rule::in(array_keys(SensTrajet::options()))],
            'point_arret'     => ['nullable', 'string', 'max:200'],
        ];
    }
}