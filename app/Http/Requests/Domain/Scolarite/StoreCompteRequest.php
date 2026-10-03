<?php

namespace App\Http\Requests\Domain\Scolarite;

use App\Domain\Scolarite\Types\StatutCompte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * L'espace familial vient de l'URL (/espaces/{espace}/comptes) : le
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
        $compteId = $this->route('compte')?->id;

        return [
            'email'         => [
                'required', 'email', 'max:150',
                Rule::unique('comptes', 'email')->ignore($compteId),
            ],
            'espace_id'     => ['required', 'integer', Rule::exists('espaces', 'id')->where('etat', 1)],
            'parent_id'     => ['required', 'integer', Rule::exists('parent_eleves', 'id')->where('etat', 1)],
            'statut_compte' => ['nullable', Rule::in(array_column(StatutCompte::cases(), 'value'))],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'      => "L'email est obligatoire.",
            'email.unique'        => "Un compte avec cet email existe déjà.",
            'espace_id.required'  => "L'espace familial est obligatoire.",
            'parent_id.required'  => "Le parent titulaire est obligatoire.",
            'parent_id.exists'    => "Le parent sélectionné est introuvable.",
            'statut_compte.in'    => "Le statut sélectionné est invalide.",
        ];
    }
}