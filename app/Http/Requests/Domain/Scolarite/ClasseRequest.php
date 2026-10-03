<?php

namespace App\Http\Requests\Domain\Scolarite;

use App\Support\AnneeScolaireContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création et modification d'une classe. Deux classes d'une même année
 * scolaire ne portent pas le même libellé.
 */
class ClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('structure.gerer');
    }

    public function rules(): array
    {
        $classeId = $this->route('classe')?->id;

        return [
            'libelle' => [
                'required', 'string', 'max:255',
                Rule::unique('classes', 'libelle')
                    ->where('annee_id', app(AnneeScolaireContext::class)->id())
                    ->where('etat', 1)
                    ->ignore($classeId),
            ],
            'emplacement' => ['nullable', 'string', 'max:255'],
            'niveau_id'   => ['required', 'integer', Rule::exists('niveaux', 'id')->where('etat', 1)],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'   => 'Le libellé de la classe est obligatoire.',
            'libelle.unique'     => 'Une classe porte déjà ce libellé pour cette année scolaire.',
            'niveau_id.required' => 'Veuillez sélectionner un niveau.',
            'niveau_id.exists'   => "Le niveau sélectionné n'existe pas ou n'est plus disponible.",
        ];
    }
}
