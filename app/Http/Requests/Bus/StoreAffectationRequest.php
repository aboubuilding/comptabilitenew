<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAffectationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'voiture_id'        => 'required|exists:voitures,id',
            'chauffeur_id'      => 'nullable|exists:chauffeurs,id',
            'date_debut'        => 'required|date',
            'date_fin'          => 'nullable|date|after_or_equal:date_debut',
            'motif'             => 'nullable|string',
            'type_affectation'  => 'required|integer|in:1,2',
        ];
    }
}