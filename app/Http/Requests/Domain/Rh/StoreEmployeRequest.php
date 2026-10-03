<?php

namespace App\Http\Requests\Domain\Rh;

use App\Domain\Rh\Types\Genre;
use App\Domain\Rh\Types\SituationFamiliale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employes.gerer');
    }

    public function rules(): array
    {
        return [
            'nom_prenom'   => ['required', 'string', 'max:150'],
            'numero_cnss'  => ['nullable', 'string', 'max:50'],
            'quartier'     => ['nullable', 'string', 'max:100'],
            'ville'        => ['nullable', 'string', 'max:100'],
            'telephone'    => ['nullable', 'string', 'max:30'],

            'date_embauche'         => ['nullable', 'date'],
            'date_naissance'        => ['nullable', 'date', 'before:today'],
            'lieu_naissance'        => ['nullable', 'string', 'max:150'],
            'email'                 => ['nullable', 'email', 'max:150'],
            'genre'                 => ['nullable', Rule::in(array_keys(Genre::options()))],
            'situation_familiale'   => ['nullable', Rule::in(array_keys(SituationFamiliale::options()))],
            'date_fin_contrat'      => ['nullable', 'date', 'after:date_embauche'],

            'is_cnss'               => ['nullable', 'boolean'],
            'is_amu'                => ['nullable', 'boolean'],
            'nbre_enfant'           => ['nullable', 'integer', 'min:0', 'max:30'],

            'is_pension_validite'   => ['nullable', 'boolean'],
            'taux_pension_validite' => ['nullable', 'integer', 'min:0', 'max:100'],
            'nature_pension_validite'=> ['nullable', 'numeric', 'min:0'],

            'salaire'               => ['required', 'numeric', 'min:0'],
            'profession_id'         => ['nullable', 'exists:professions,id'],

            'mode_paiement'    => ['required', 'in:billette,banque'],
'banque'           => ['nullable', 'string', 'max:100'],
'iban'             => ['nullable', 'string', 'max:50'],
'titulaire_compte' => ['nullable', 'string', 'max:150'],

'irpp_mode' => ['required', 'in:auto,manuel'],
        ];
    }
}