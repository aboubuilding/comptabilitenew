<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNiveauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'      => 'required|string|max:255|unique:niveaux,libelle',
            'description'  => 'nullable|string',
            'numero_ordre' => 'nullable|integer|min:0',
            'cycle_id'     => 'required|integer|exists:cycles,id',
            'etat'         => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.unique' => 'Ce niveau existe déjà.',
            'cycle_id.exists' => 'Le cycle sélectionné est invalide.',
        ];
    }
}