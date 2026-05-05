<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChauffeurRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('id');
        return [
            'nom'               => 'required|string|max:255',
            'prenom'            => 'required|string|max:255',
            'permis_conduire'   => ['nullable', 'string', 'max:50', Rule::unique('chauffeurs', 'permis_conduire')->ignore($id)],
            'date_validite_permis' => 'nullable|date',
            'telephone'         => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:255',
            'adresse'           => 'nullable|string',
            'statut'            => 'nullable|integer|in:0,1',
        ];
    }
}