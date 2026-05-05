<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFournisseurRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'raison_social'     => 'required|string|max:255|unique:fournisseurs,raison_social',
            'nom_contact'       => 'nullable|string|max:255',
            'telephone_contact' => 'nullable|string|max:50',
            'adresse'           => 'nullable|string',
            'etat'              => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'raison_social.unique' => 'Ce fournisseur existe déjà.',
        ];
    }
}