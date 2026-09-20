<?php

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Un seul FormRequest pour création ET modification, comme sur les
 * autres modules. Champs regroupés par section (état civil, santé,
 * contact d'urgence) pour matcher le formulaire prévu (cahier des
 * charges : "état civil, informations médicales, contact d'urgence").
 */
class EleveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Secrétariat (gestion), cf. cahier des charges §4 (1.1)
    }

    public function rules(): array
    {
        return [
            // État civil
            'matricule'      => ['nullable', 'string', 'max:50'],
            'nom'            => ['required', 'string', 'max:255'],
            'prenom'         => ['required', 'string', 'max:255'],
            'prenom_usuel'   => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'sexe'           => ['required', 'integer', 'in:1,2'],
            'nationalite_id' => ['nullable', 'integer', 'exists:nationalites,id'],
            'ecole_provenance' => ['nullable', 'string', 'max:255'],
            'photo'          => ['nullable', 'image', 'max:2048'],
            'carte_identite' => ['nullable', 'file', 'max:4096'],

            // Santé
            'groupe_id'          => ['nullable', 'integer'],
            'allergie'           => ['nullable', 'string'],
            'nom_medecin'        => ['nullable', 'string', 'max:255'],
            'numero_medecin'     => ['nullable', 'string', 'max:30'],
            'certificat_medical' => ['nullable', 'file', 'max:4096'],
            'vaccin_1' => ['nullable', 'string', 'max:100'],
            'vaccin_2' => ['nullable', 'string', 'max:100'],
            'vaccin_3' => ['nullable', 'string', 'max:100'],
            'vaccin_4' => ['nullable', 'string', 'max:100'],
            'vaccin_5' => ['nullable', 'string', 'max:100'],

            // Contact d'urgence
            'personne_prevenir'        => ['nullable', 'string'],
            'numero_personne_prevenir' => ['nullable', 'string', 'max:30'],
            'lien_parente_personne'    => ['nullable', 'integer', 'in:1,2,3,4'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'           => 'Le nom est obligatoire.',
            'prenom.required'        => 'Le prénom est obligatoire.',
            'sexe.required'          => 'Le sexe est obligatoire.',
            'date_naissance.before'  => 'La date de naissance doit être dans le passé.',
            'photo.image'            => 'La photo doit être une image.',
            'photo.max'              => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nom'                   => 'nom',
            'prenom'                => 'prénom',
            'date_naissance'        => 'date de naissance',
            'nationalite_id'        => 'nationalité',
            'lien_parente_personne' => 'lien de parenté',
        ];
    }
}