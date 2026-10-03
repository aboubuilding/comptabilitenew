<?php

namespace App\Http\Requests\Domain\Logistique;

use App\Domain\Logistique\Types\TypeClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ventes.gerer');
    }

    public function rules(): array
    {
        return [
            'nom_raison_sociale' => ['required', 'string', 'max:200'],
            'type_client'        => ['required', Rule::in(array_keys(TypeClient::options()))],
            'telephone'          => ['nullable', 'string', 'max:30'],
            'email'              => ['nullable', 'email', 'max:150'],
            'adresse'            => ['nullable', 'string', 'max:1000'],
            'nif'                => ['nullable', 'string', 'max:50'],
            'rccm'               => ['nullable', 'string', 'max:50'],
        ];
    }
}