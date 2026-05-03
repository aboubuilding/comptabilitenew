<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaisseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'libelle'         => 'required|string|max:255',
            'responsable_id'  => 'nullable|integer|exists:users,id',
            'annee_id'        => 'nullable|integer|exists:annees_scolaires,id',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'        => 'Le libellé de la caisse est obligatoire.',
            'responsable_id.exists'   => 'Le responsable sélectionné est invalide.',
            'annee_id.exists'         => 'L\'année scolaire sélectionnée est invalide.',
        ];
    }
}