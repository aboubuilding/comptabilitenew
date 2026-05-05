<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoutiqueRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'libelle'     => 'required|string|max:255',
            'responsable' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'adresse'     => 'nullable|string',
            'telephone'   => 'nullable|string|max:50',
            'type'        => 'required|in:1,2',
            'etat'        => 'sometimes|in:0,1',
        ];
    }
}