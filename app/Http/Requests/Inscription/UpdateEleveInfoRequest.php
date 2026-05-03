<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEleveInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'                  => 'nullable|string|max:255',
            'prenom'               => 'nullable|string|max:255',
            'prenom_usuel'         => 'nullable|string|max:255',
            'date_naissance'       => 'nullable|date',
            'lieu_naissance'       => 'nullable|string|max:255',
            'sexe'                 => 'nullable|in:0,1,2', // 0=fille,1=garçon,2=autre
            'nationalite_id'       => 'nullable|integer|exists:nationalites,id',
            'ecole_provenance'     => 'nullable|string|max:255',
            'personne_prevenir'    => 'nullable|string|max:255',
            'numero_personne_prevenir' => 'nullable|string|max:50',
            'lien_parente_personne'=> 'nullable|integer|min:1|max:10',
            'allergie'             => 'nullable|string',
            'groupe_id'            => 'nullable|integer',
            'certificat_medical'   => 'nullable|string|max:255',
            'vaccin_1'             => 'nullable|string|max:255',
            'vaccin_2'             => 'nullable|string|max:255',
            'vaccin_3'             => 'nullable|string|max:255',
            'vaccin_4'             => 'nullable|string|max:255',
            'vaccin_5'             => 'nullable|string|max:255',
            'nom_medecin'          => 'nullable|string|max:255',
            'numero_medecin'       => 'nullable|string|max:50',
            'photo'                => 'nullable|string|max:255',
            'carte_identite'       => 'nullable|string|max:255',
            'naissance'            => 'nullable|string|max:255',
        ];
    }
}