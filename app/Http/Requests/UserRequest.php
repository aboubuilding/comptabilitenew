<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Les règles de validation.
     */
    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'nom' => ['nullable', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'login' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'login')->ignore($userId)
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'photo' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'integer', Rule::in([1, 2, 3, 4, 5, 6, 7, 8])],
            'etat' => ['nullable', 'integer', Rule::in([0, 1])],
        ];

        // Mot de passe requis uniquement pour la création
        if (!$isUpdate) {
            $rules['mot_passe'] = ['nullable', 'string', 'min:6'];
        } else {
            $rules['mot_passe'] = ['nullable', 'string', 'min:6'];
        }

        return $rules;
    }

    /**
     * Messages de validation personnalisés.
     */
    public function messages(): array
    {
        return [
            'login.unique' => 'Ce login est déjà utilisé.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
            'mot_passe.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'role.in' => 'Le rôle sélectionné est invalide.',
            'etat.in' => 'L\'état sélectionné est invalide.',
        ];
    }

    /**
     * Préparer les données pour la validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nom' => trim($this->nom ?? ''),
            'prenom' => trim($this->prenom ?? ''),
            'login' => trim($this->login ?? ''),
            'email' => trim($this->email ?? ''),
            'photo' => trim($this->photo ?? ''),
        ]);
    }

    /**
     * Obtenir les données validées avec valeurs par défaut.
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();

        $data['etat'] = $data['etat'] ?? 1;
        $data['role'] = $data['role'] ?? 1;

        return $data;
    }
}
