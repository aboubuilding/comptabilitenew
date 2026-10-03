<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntretienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transport.gerer');
    }

    public function rules(): array
    {
        return [
            'voiture_id'     => ['required', 'exists:voitures,id'],
            'date_entretien' => ['required', 'date'],
            'type_entretien' => ['required', 'string', 'max:100'],
            'cout'           => ['nullable', 'numeric', 'min:0'],
            'kilometrage'    => ['required', 'integer', 'min:0'],
            'observations'   => ['nullable', 'string', 'max:2000'],
            'effectue_par'   => ['nullable', 'exists:chauffeurs,id'],
        ];
    }
}