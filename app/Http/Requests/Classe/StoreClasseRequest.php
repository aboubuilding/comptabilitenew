<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'      => 'required|string|max:255',
            'emplacement'  => 'nullable|string|max:255',
            'cycle_id'     => 'required|integer|exists:cycles,id',
            'niveau_id'    => 'required|integer|exists:niveaux,id',
         
        ];
    }

    public function messages(): array
    {
        return [
            'cycle_id.exists'  => 'Le cycle sélectionné est invalide.',
            'niveau_id.exists' => 'Le niveau sélectionné est invalide.',
            
        ];
    }
}