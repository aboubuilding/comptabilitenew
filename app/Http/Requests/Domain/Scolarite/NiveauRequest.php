<?php

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Un seul FormRequest pour la création ET la modification d'un niveau —
 * même principe que CycleRequest. Pas de contrainte d'unicité ici (le
 * schéma n'en impose pas sur niveaux.libelle), donc les règles sont
 * strictement identiques entre les deux cas.
 */
class NiveauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy, voir doc d'architecture
    }

    public function rules(): array
    {
        return [
            'libelle'      => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'numero_ordre' => ['nullable', 'integer', 'min:0'],
            'cycle_id'     => ['required', 'integer', 'exists:cycles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'      => 'Le libellé du niveau est obligatoire.',
            'libelle.string'        => 'Le libellé du niveau doit être un texte.',
            'libelle.max'           => 'Le libellé du niveau ne peut pas dépasser :max caractères.',
            'numero_ordre.integer'  => "L'ordre doit être un nombre entier.",
            'numero_ordre.min'      => "L'ordre ne peut pas être négatif.",
            'cycle_id.required'     => 'Veuillez sélectionner un cycle.',
            'cycle_id.exists'       => "Le cycle sélectionné n'existe pas ou n'est plus disponible.",
        ];
    }

    public function attributes(): array
    {
        return [
            'libelle'      => 'libellé',
            'description'  => 'description',
            'numero_ordre' => 'ordre',
            'cycle_id'     => 'cycle',
        ];
    }
}