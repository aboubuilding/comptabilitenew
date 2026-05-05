<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFournisseurRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('id');
        return [
            'raison_social'     => ['required', 'string', 'max:255', Rule::unique('fournisseurs', 'raison_social')->ignore($id)],
            'nom_contact'       => 'nullable|string|max:255',
            'telephone_contact' => 'nullable|string|max:50',
            'adresse'           => 'nullable|string',
            'etat'              => 'sometimes|integer|in:0,1',
        ];
    }
}