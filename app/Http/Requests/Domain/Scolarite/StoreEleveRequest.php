<?php

namespace App\Http\Requests\Domain\Scolarite;

use App\Domain\Scolarite\Types\LienParente;
use App\Domain\Scolarite\Types\Sexe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest unique pour la création ET la modification d'un élève.
 */
class StoreEleveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'                      => ['required', 'string', 'max:150'],
            'prenom'                   => ['required', 'string', 'max:150'],
            'prenom_usuel'             => ['nullable', 'string', 'max:150'],
            'ecole_provenance'         => ['nullable', 'string', 'max:150'],
            'date_naissance'           => ['required', 'date', 'before:today'],
            'lieu_naissance'           => ['nullable', 'string', 'max:150'],
            'sexe'                     => ['required', Rule::in(array_column(Sexe::cases(), 'value'))],
            'nationalite_id'           => ['nullable', 'integer', Rule::exists('nationalites', 'id')->where('etat', 1)],

            'nom_medecin'              => ['nullable', 'string', 'max:150'],
            'numero_medecin'           => ['nullable', 'string', 'max:50'],
            'personne_prevenir'        => ['nullable', 'string', 'max:150'],
            'numero_personne_prevenir' => ['nullable', 'string', 'max:50'],
            'lien_parente_personne'    => ['nullable', Rule::in(array_column(LienParente::cases(), 'value'))],

            'groupe_id'                => ['nullable', 'integer'],
            'vaccin_1'                 => ['nullable', 'string', 'max:150'],
            'vaccin_2'                 => ['nullable', 'string', 'max:150'],
            'vaccin_3'                 => ['nullable', 'string', 'max:150'],
            'vaccin_4'                 => ['nullable', 'string', 'max:150'],
            'vaccin_5'                 => ['nullable', 'string', 'max:150'],
            'allergie'                 => ['nullable', 'string'],

            'photo'                    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'carte_identite'           => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'            => "Le nom est obligatoire.",
            'prenom.required'         => "Le prénom est obligatoire.",
            'date_naissance.required' => "La date de naissance est obligatoire.",
            'date_naissance.before'   => "La date de naissance doit être antérieure à aujourd'hui.",
            'sexe.required'           => "Le sexe est obligatoire.",
            'sexe.in'                 => "Le sexe sélectionné est invalide.",
            'nationalite_id.exists'   => "La nationalité sélectionnée est introuvable.",
            'photo.image'             => "Le fichier photo doit être une image.",
            'photo.max'               => "La photo ne doit pas dépasser 2 Mo.",
        ];
    }
}