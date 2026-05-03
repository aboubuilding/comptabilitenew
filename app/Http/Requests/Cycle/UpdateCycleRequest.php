<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'libelle' => ['required', 'string', 'max:255', Rule::unique('cycles', 'libelle')->ignore($id)],
            'etat'    => 'sometimes|integer|in:0,1',
        ];
    }
}