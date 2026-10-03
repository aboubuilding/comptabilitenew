<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StorePreInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('activites.inscrire');
    }

    public function rules(): array
    {
        return [
            'evenement_id'   => ['required', 'exists:evenements,id'],
            'inscription_id' => ['required', 'exists:inscriptions,id'],
        ];
    }
}