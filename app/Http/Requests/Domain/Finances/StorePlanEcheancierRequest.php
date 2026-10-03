<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanEcheancierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('frais.gerer');
    }

    public function rules(): array
    {
        return [
            'nom'                         => ['required', 'string', 'max:150'],
            'description'                 => ['nullable', 'string', 'max:1000'],
            'lignes'                      => ['required', 'array', 'min:1'],
            'lignes.*.libelle'            => ['required', 'string', 'max:100'],
            'lignes.*.ordre'              => ['nullable', 'integer', 'min:1'],
            'lignes.*.jour_echeance'      => ['nullable', 'integer', 'between:1,31'],
            'lignes.*.date_echeance'      => ['nullable', 'date'],
            'lignes.*.montant'            => ['nullable', 'numeric', 'min:0'],
            'lignes.*.pourcentage'        => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'              => 'Le nom du plan est obligatoire.',
            'lignes.required'           => "Un plan d'échéancier doit comporter au moins une échéance.",
            'lignes.min'                => "Un plan d'échéancier doit comporter au moins une échéance.",
            'lignes.*.libelle.required' => 'Chaque échéance doit avoir un libellé.',
        ];
    }
}