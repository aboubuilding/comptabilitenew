<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InscriptionCantineRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'inscription_id' => 'required|exists:inscriptions,id',
            'date_debut'     => 'required|date',
            'frais_ecole_id' => 'nullable|exists:frais_ecoles,id',
        ];
    }
}