<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StorePretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bibliotheque.preter');
    }

    public function rules(): array
    {
        return [
            'livre_id'         => ['required', 'exists:livres,id'],
            'inscription_id'   => ['required', 'exists:inscriptions,id'],
            'date_pret'        => ['required', 'date'],
            'date_retour_pret' => ['required', 'date', 'after:date_pret'],
            'caution'          => ['nullable', 'numeric', 'min:0'],
            'observations'     => ['nullable', 'string', 'max:2000'],
        ];
    }
}