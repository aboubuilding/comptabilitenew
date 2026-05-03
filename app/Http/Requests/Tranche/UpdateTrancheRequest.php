<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrancheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'        => 'required|string|max:255',
            'date_butoire'   => 'required|date',
            'frais_ecole_id' => 'required|integer|exists:frais_ecoles,id',
            'type_frais'     => 'required|integer|in:1,2,3',
            'taux'           => 'required|integer|min:1|max:100',
            'etat'           => 'sometimes|integer|in:0,1',
        ];
    }
}