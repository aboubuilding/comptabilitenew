<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnneeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id'); // ou $this->annee si paramètre route

        return [
            'libelle'      => ['required', 'string', 'max:255', Rule::unique('annees', 'libelle')->ignore($id)],
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
            'libelle.unique' => 'Cet intitulé est déjà utilisé par une autre année.',
            'date_fin.after' => 'La date de fin doit être après la rentrée.',
        ];
    }
}