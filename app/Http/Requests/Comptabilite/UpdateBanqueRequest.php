<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBanqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'nom'  => ['required', 'string', 'max:255', Rule::unique('banques', 'nom')->ignore($id)],
            'etat' => 'sometimes|integer|in:0,1',
        ];
    }
}