<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StoreLigneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transport.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'       => ['required', 'string', 'max:150'],
            'prix_minimal'  => ['nullable', 'numeric', 'min:0'],
            'prix_plafond'  => ['nullable', 'numeric', 'min:0', 'gte:prix_minimal'],
        ];
    }
}