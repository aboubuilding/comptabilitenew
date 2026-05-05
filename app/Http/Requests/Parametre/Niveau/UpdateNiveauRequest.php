<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNiveauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'libelle'      => ['required', 'string', 'max:255', Rule::unique('niveaux', 'libelle')->ignore($id)],
            'description'  => 'nullable|string',
            'numero_ordre' => 'nullable|integer|min:0',
            'cycle_id'     => 'required|integer|exists:cycles,id',
            'etat'         => 'sometimes|integer|in:0,1',
        ];
    }
}