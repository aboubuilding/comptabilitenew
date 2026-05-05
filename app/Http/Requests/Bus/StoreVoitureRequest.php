<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoitureRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'marque'            => 'required|string|max:255',
            'modele'            => 'nullable|string|max:255',
            'plaque'            => 'required|string|max:50|unique:voitures,plaque',
            'nombre_place'      => 'nullable|integer|min:1|max:90',
            'annee_fabrication' => 'nullable|integer|min:1900|max:' . (date('Y')+1),
            'couleur'           => 'nullable|string|max:50',
            'numero_chassis'    => 'nullable|string|max:50|unique:voitures,numero_chassis',
            'date_achat'        => 'nullable|date',
            'prix_achat'        => 'nullable|numeric|min:0',
            'fournisseur'       => 'nullable|string|max:255',
            'kilometrage_actuel'=> 'nullable|integer|min:0',
            'statut'            => 'nullable|integer|in:1,2,3,4',
            'annee_id'          => 'nullable|exists:annees,id',
        ];
    }
}