<?php

namespace App\Http\Requests\Domain\Scolarite;

use App\Domain\Scolarite\Types\RoleParent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * L'espace familial vient de l'URL (/espaces/{espace}/parents) : le
     * formulaire n'a pas à le renvoyer.
     */
    protected function prepareForValidation(): void
    {
        $espace = $this->route('espace');

        if ($espace !== null) {
            $this->merge(['espace_id' => is_object($espace) ? $espace->getKey() : (int) $espace]);
        }
    }

    public function rules(): array
    {
        return [
            'espace_id'      => ['required', 'integer', Rule::exists('espaces', 'id')->where('etat', 1)],
            'nom_parent'     => ['required', 'string', 'max:150'],
            'prenom_parent'  => ['required', 'string', 'max:150'],
            'telephone'      => ['nullable', 'string', 'max:50'],
            'whatsapp'       => ['nullable', 'string', 'max:50'],
            'email'          => ['nullable', 'email', 'max:150'],
            'profession'     => ['nullable', 'string', 'max:150'],
            'quartier'       => ['nullable', 'string', 'max:150'],
            'adresse'        => ['nullable', 'string'],
            'nationalite_id' => ['nullable', 'integer', Rule::exists('nationalites', 'id')->where('etat', 1)],
            'role'           => ['nullable', Rule::in(array_column(RoleParent::cases(), 'value'))],
            'is_principal'   => ['nullable', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'espace_id.required'    => "L'espace familial est obligatoire.",
            'espace_id.exists'      => "L'espace familial sélectionné est introuvable.",
            'nom_parent.required'   => "Le nom est obligatoire.",
            'prenom_parent.required'=> "Le prénom est obligatoire.",
            'email.email'           => "L'email n'est pas valide.",
            'role.in'               => "Le rôle sélectionné est invalide.",
        ];
    }
}