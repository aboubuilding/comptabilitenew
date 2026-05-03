<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // À adapter avec vos permissions
    }

    public function rules(): array
    {
        return [
            'libelle' => 'required|string|max:255|unique:cycles,libelle',
            'etat'    => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.unique' => 'Ce cycle existe déjà.',
        ];
    }
}