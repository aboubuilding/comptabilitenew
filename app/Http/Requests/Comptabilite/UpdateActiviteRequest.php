<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActiviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'libelle'      => 'required|string|max:255',
            'description'  => 'nullable|string',
            'montant'      => 'required|numeric|min:0',
            'niveau_id'    => 'required|integer|exists:niveaux,id',
            'etat'         => 'sometimes|integer|in:0,1',
        ];
    }
}