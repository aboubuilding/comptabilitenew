<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrancheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'        => 'required|string|max:255',
            'date_butoire'   => 'required|date|after_or_equal:today',
            'frais_ecole_id' => 'required|integer|exists:frais_ecoles,id',
            'type_frais'     => 'required|integer|in:1,2,3', // Ex: 1=inscription, 2=scolarité, 3=cantine
            'taux'           => 'required|integer|min:1|max:100',
            'etat'           => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'frais_ecole_id.exists' => 'Le frais scolaire sélectionné est invalide.',
            'date_butoire.after_or_equal' => 'La date butoir doit être aujourd\'hui ou ultérieure.',
            'taux.min' => 'Le taux doit être au moins 1%.',
            'taux.max' => 'Le taux ne peut pas dépasser 100%.',
        ];
    }
}