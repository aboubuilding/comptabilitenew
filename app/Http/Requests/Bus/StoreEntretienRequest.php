<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntretienRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'voiture_id'        => 'required|exists:voitures,id',
            'date_entretien'    => 'required|date',
            'type_entretien'    => 'required|string|max:255',
            'cout'              => 'nullable|numeric|min:0',
            'kilometrage'       => 'required|integer|min:0',
            'observations'      => 'nullable|string',
            'effectue_par'      => 'nullable|exists:chauffeurs,id',
        ];
    }
}