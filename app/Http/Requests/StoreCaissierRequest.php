<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaissierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'login'      => 'required|string|unique:users,login|max:100',
            'mot_passe'  => 'required|string|min:6|max:100',
            'nom'        => 'nullable|string|max:100',
            'prenom'     => 'nullable|string|max:100',
            'email'      => 'nullable|email|max:255',
            'photo'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'login.required'     => 'Le login est obligatoire.',
            'login.unique'       => 'Ce login est déjà utilisé.',
            'mot_passe.min'      => 'Le mot de passe doit contenir au moins 6 caractères.',
            'photo.image'        => 'La photo doit être une image valide.',
            'photo.max'          => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }
}