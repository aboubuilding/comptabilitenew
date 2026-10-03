<?php

namespace App\Http\Requests\Domain\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest unique pour la création ET la modification d'un utilisateur.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'nom'        => ['required', 'string', 'max:100'],
            'prenom'     => ['required', 'string', 'max:100'],
            'login'      => [
                'required', 'string', 'max:100',
                Rule::unique('users', 'login')->ignore($userId),
            ],
            'email'      => [
                'nullable', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'mot_passe'  => [$userId ? 'nullable' : 'required', 'string', 'min:6'],
            'role_id'    => ['required', 'integer', Rule::exists('roles', 'id')->where('etat', 1)],
            'profils_complementaires'   => ['nullable', 'array'],
            'profils_complementaires.*' => ['integer', Rule::exists('roles', 'id')->where('etat', 1)],
            'photo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'etat'       => ['nullable', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'       => "Le nom est obligatoire.",
            'prenom.required'    => "Le prénom est obligatoire.",
            'login.required'     => "Le login est obligatoire.",
            'login.unique'       => "Ce login est déjà utilisé.",
            'email.email'        => "L'email n'est pas valide.",
            'email.unique'       => "Cet email est déjà utilisé.",
            'mot_passe.required' => "Le mot de passe est obligatoire.",
            'mot_passe.min'      => "Le mot de passe doit contenir au moins 6 caractères.",
            'role_id.required'   => "Le profil est obligatoire.",
            'role_id.exists'     => "Le profil sélectionné est introuvable.",
            'photo.image'        => "Le fichier doit être une image.",
            'photo.max'          => "L'image ne doit pas dépasser 2 Mo.",
        ];
    }
}