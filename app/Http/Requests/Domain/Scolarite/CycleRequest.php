<?php

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Un seul FormRequest pour la création ET la modification d'un cycle.
 * Pas de champ 'etat' : la modale ne propose plus d'activer/désactiver
 * directement (BaseRepository::create() initialise déjà à ACTIF), le
 * seul chemin de désactivation reste le bouton "Désactiver" sur chaque
 * ligne.
 */
class CycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy, voir doc d'architecture
    }

    public function rules(): array
    {
        return [
            'libelle' => [
                'required', 'string', 'max:255',
                Rule::unique('cycles', 'libelle')->ignore($this->route('cycle')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé du cycle est obligatoire.',
            'libelle.string'   => 'Le libellé du cycle doit être un texte.',
            'libelle.max'      => 'Le libellé du cycle ne peut pas dépasser :max caractères.',
            'libelle.unique'   => 'Ce libellé de cycle existe déjà.',
        ];
    }

    public function attributes(): array
    {
        return [
            'libelle' => 'libellé',
        ];
    }
}