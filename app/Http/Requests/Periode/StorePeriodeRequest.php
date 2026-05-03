<?php

namespace App\Http\Requests\Periode;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeriodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // À adapter avec vos permissions
    }

    public function rules(): array
    {
        return [
            'libelle'    => 'required|string|max:255',
            'date_debut' => 'required|date_format:Y-m-d',
            'date_fin'   => 'required|date_format:Y-m-d|after_or_equal:date_debut',
            'annee_id'   => 'required|integer|exists:annees,id',
            'etat'       => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'annee_id.exists'         => 'L\'année sélectionnée n\'existe pas.',
        ];
    }
}