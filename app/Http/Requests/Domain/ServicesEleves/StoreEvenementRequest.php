<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Types\TypeEvenement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvenementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('activites.gerer');
    }

    public function rules(): array
    {
        return [
            'nom'                      => ['required', 'string', 'max:200'],
            'type'                     => ['required', Rule::in(array_keys(TypeEvenement::options()))],
            'date_evenement'           => ['required', 'date'],
            'participation'            => ['required', 'numeric', 'min:0'],
            // Capacité limitée : le nombre de places et la date limite de confirmation
            // sont alors obligatoires (cahier des charges § 4.4).
            'capacite'                 => ['nullable', 'required_if:capacite_limitee,1', 'integer', 'min:1'],
            'capacite_limitee'         => ['nullable', 'boolean'],
            'date_limite_confirmation' => ['nullable', 'required_if:capacite_limitee,1', 'date', 'before:date_evenement'],
            'description'              => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'capacite.required_if'                 => 'Indiquez le nombre de places d\'un événement à capacité limitée.',
            'date_limite_confirmation.required_if' => 'La date limite de confirmation est obligatoire pour un événement à capacité limitée.',
            'date_limite_confirmation.before'      => 'La date limite de confirmation doit précéder la date de l\'événement.',
        ];
    }
}