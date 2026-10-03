<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StoreInscriptionActiviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('activites.inscrire');
    }

    public function rules(): array
    {
        return [
            'eleve_id'         => ['required', 'exists:eleves,id'],
            'activite_id'      => ['required', 'exists:activites,id'],
            'montant_du'       => ['nullable', 'numeric', 'min:0'],
            'date_inscription' => ['nullable', 'date'],
        ];
    }
}