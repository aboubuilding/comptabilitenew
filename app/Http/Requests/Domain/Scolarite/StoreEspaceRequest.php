<?php

namespace App\Http\Requests\Domain\Scolarite;

use Illuminate\Foundation\Http\FormRequest;

class StoreEspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_famille' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_famille.required' => "Le nom de la famille est obligatoire.",
        ];
    }
}