<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnneeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // À ajuster selon vos permissions
    }

    public function rules(): array
    {
        return [
            'libelle'      => 'required|string|max:255|unique:annees,libelle',
            'date_rentree' => 'required|date',
            'date_fin'     => 'required|date|after:date_rentree',
            'date_ouverture_inscription' => 'nullable|date|before:date_fermeture_reinscription',
            'date_fermeture_reinscription' => 'nullable|date|after:date_ouverture_inscription',
            'statut_annee' => 'nullable|in:0,1,2',
            'etat'         => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.unique' => 'Cette année scolaire existe déjà.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de rentrée.',
            'date_ouverture_inscription.before' => 'L\'ouverture des inscriptions doit être avant la fermeture.',
        ];
    }
}