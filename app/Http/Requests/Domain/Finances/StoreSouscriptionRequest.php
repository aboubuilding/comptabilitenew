<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class StoreSouscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('souscriptions.gerer');
    }

    public function rules(): array
    {
        return [
            'frais_ecole_id'   => ['required', 'exists:frais_ecoles,id'],
            'inscription_id'   => ['required', 'exists:inscriptions,id'],
            'taux_remise'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'date_souscription'=> ['nullable', 'date'],
        ];
    }
}