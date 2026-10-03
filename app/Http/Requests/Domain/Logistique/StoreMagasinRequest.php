<?php

namespace App\Http\Requests\Domain\Logistique;

use App\Domain\Logistique\Types\TypeMagasin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMagasinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'      => ['required', 'string', 'max:150'],
            'responsable'  => ['nullable', 'string', 'max:150'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'adresse'      => ['nullable', 'string', 'max:1000'],
            'telephone'    => ['nullable', 'string', 'max:30'],
            'type'         => ['required', Rule::in(array_keys(TypeMagasin::options()))],
        ];
    }
}