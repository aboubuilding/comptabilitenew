<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeriodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id'); // ou $this->periode

        return [
            'libelle'    => 'required|string|max:255',
            'date_debut' => 'required|date_format:Y-m-d',
            'date_fin'   => 'required|date_format:Y-m-d|after_or_equal:date_debut',
            'annee_id'   => 'required|integer|exists:annees,id',
            'etat'       => 'sometimes|integer|in:0,1',
        ];
    }
}