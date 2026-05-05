<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarburantRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'voiture_id'        => 'required|exists:voitures,id',
            'date_plein'        => 'required|date',
            'quantite_litres'   => 'required|numeric|min:0.01',
            'prix_unitaire'     => 'required|numeric|min:0',
            'montant_total'     => 'required|numeric|min:0',
            'kilometrage'       => 'required|integer|min:0',
            'station_service'   => 'nullable|string|max:255',
            'facture'           => 'nullable|string|max:255',
        ];
    }
}