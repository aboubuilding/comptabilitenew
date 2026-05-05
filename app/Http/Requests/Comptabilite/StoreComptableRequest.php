<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComptableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'        => 'required|string|max:255',
            'prenom'     => 'required|string|max:255',
            'login'      => 'required|string|max:255|unique:users,login',
            'email'      => 'required|email|max:255|unique:users,email',
            'mot_passe'  => 'required|string|min:6',
            'photo'      => 'nullable|string|max:255',
            'etat'       => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'login.unique' => 'Ce login existe déjà.',
            'email.unique' => 'Cet email est déjà utilisé.',
        ];
    }
}