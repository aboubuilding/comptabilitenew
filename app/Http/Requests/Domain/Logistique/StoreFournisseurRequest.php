<?php

namespace App\Http\Requests\Domain\Logistique;

use Illuminate\Foundation\Http\FormRequest;

class StoreFournisseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fournisseurs.gerer');
    }

    public function rules(): array
    {
        return [
            'raison_social'     => ['required', 'string', 'max:150'],
            'nom_contact'       => ['nullable', 'string', 'max:150'],
            'telephone_contact' => ['nullable', 'string', 'max:30'],
            'email_contact'     => ['nullable', 'email', 'max:150'],
            'adresse'           => ['nullable', 'string', 'max:1000'],
            'nif'               => ['nullable', 'string', 'max:50'],
            'rccm'              => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'raison_social.required' => 'La raison sociale est obligatoire.',
            'email_contact.email'    => "L'email du contact n'est pas valide.",
        ];
    }
}