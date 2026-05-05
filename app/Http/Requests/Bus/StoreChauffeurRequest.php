<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChauffeurRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nom'               => 'required|string|max:255',
            'prenom'            => 'required|string|max:255',
            'permis_conduire'   => 'nullable|string|max:50|unique:chauffeurs,permis_conduire',
            'date_validite_permis' => 'nullable|date',
            'telephone'         => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:255',
            'adresse'           => 'nullable|string',
            'statut'            => 'nullable|integer|in:0,1',
        ];
    }
}