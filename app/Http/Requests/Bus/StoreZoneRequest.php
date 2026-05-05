<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code'          => 'required|string|max:50|unique:zones,code',
            'libelle'       => 'required|string|max:255',
            'description'   => 'nullable|string',
            'tarif_base'    => 'nullable|numeric|min:0',
            'ordre'         => 'nullable|integer|min:0',
            'couleur'       => 'nullable|string|max:50',
            'chauffeur_id'  => 'nullable|exists:chauffeurs,id',
            'voiture_id'    => 'nullable|exists:voitures,id',
            'annee_id'      => 'nullable|exists:annees,id',
            'etat'          => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'code.unique' => 'Ce code de zone existe déjà.',
        ];
    }
}