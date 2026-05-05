<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVoitureRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('id');
        return [
            'marque'            => 'required|string|max:255',
            'modele'            => 'nullable|string|max:255',
            'plaque'            => ['required', 'string', 'max:50', Rule::unique('voitures', 'plaque')->ignore($id)],
            'nombre_place'      => 'nullable|integer|min:1|max:90',
            'annee_fabrication' => 'nullable|integer|min:1900|max:' . (date('Y')+1),
            'couleur'           => 'nullable|string|max:50',
            'numero_chassis'    => ['nullable', 'string', 'max:50', Rule::unique('voitures', 'numero_chassis')->ignore($id)],
            'date_achat'        => 'nullable|date',
            'prix_achat'        => 'nullable|numeric|min:0',
            'fournisseur'       => 'nullable|string|max:255',
            'kilometrage_actuel'=> 'nullable|integer|min:0',
            'statut'            => 'nullable|integer|in:1,2,3,4',
            'annee_id'          => 'nullable|exists:annees,id',
        ];
    }
}