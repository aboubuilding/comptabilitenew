<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrancheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('frais.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'         => ['required', 'string', 'max:100'],
            'date_butoire'    => ['required', 'date'],
            'frais_ecole_id'  => ['required', 'exists:frais_ecoles,id'],
            'type_frais'      => ['nullable', 'integer'],
            'taux'            => ['required', 'numeric', 'min:0.01', 'max:100'],
        ];
    }
}