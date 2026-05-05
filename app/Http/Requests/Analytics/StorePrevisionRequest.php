<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrevisionRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'libelle'       => 'required|string|max:255',
            'type'          => 'required|in:recette,depense',
            'montant'       => 'required|numeric|min:0',
            'date_prevision'=> 'required|date',
            'date_fin'      => 'nullable|date|after_or_equal:date_prevision',
            'periode'       => 'nullable|string|max:50',
            'annee_id'      => 'nullable|exists:annees,id',
            'categorie_id'  => 'nullable|exists:prevision_categories,id',
            'commentaire'   => 'nullable|string',
        ];
    }
}