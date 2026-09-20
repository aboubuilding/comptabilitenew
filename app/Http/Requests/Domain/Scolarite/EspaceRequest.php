<?php

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Couvre uniquement le CRUD minimal de l'espace lui-même (nom_famille).
 * Les parents rattachés, la fratrie et le compte portail ont chacun leur
 * propre FormRequest, à construire avec l'écran de détail (prochaine
 * étape).
 */
class EspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Secrétariat (gestion), cf. cahier des charges §4 (1.2)
    }

    public function rules(): array
    {
        return [
            'nom_famille' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_famille.required' => 'Le nom de famille est obligatoire.',
        ];
    }
}