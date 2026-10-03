<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StoreInscriptionCantineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cantine.inscrire');
    }

    public function rules(): array
    {
        return [
            'inscription_id'  => ['required', 'exists:inscriptions,id'],
            'date_debut'      => ['required', 'date'],
            'montant_mensuel' => ['required', 'numeric', 'min:0.01'],
            'nombre_mois'     => ['required', 'integer', 'min:1', 'max:12'],
            'frais_ecole_id'  => ['nullable', 'exists:frais_ecoles,id'],
        ];
    }
}