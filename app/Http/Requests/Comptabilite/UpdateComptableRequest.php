<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComptableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'nom'        => 'required|string|max:255',
            'prenom'     => 'required|string|max:255',
            'login'      => ['required', 'string', 'max:255', Rule::unique('users', 'login')->ignore($id)],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'mot_passe'  => 'nullable|string|min:6',
            'photo'      => 'nullable|string|max:255',
            'etat'       => 'sometimes|integer|in:0,1',
        ];
    }
}