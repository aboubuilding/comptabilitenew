<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepenseRequest extends FormRequest
{
    /**
     * Autoriser la requête
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation (uniquement les champs du formulaire)
     */
    public function rules(): array
    {
        return [
            'libelle'              => 'required|string|max:255',
            'motif_depense'        => 'required|string|max:500',
            'montant'              => 'required|numeric|min:0.01',
            'beneficiaire'         => 'required|string|max:255',
            'date_depense'         => 'nullable|date',
            'justificatif_demande' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }

    /**
     * Messages d'erreur simples et explicites
     */
    public function messages(): array
    {
        return [
            'libelle.required'       => 'Le libellé est obligatoire.',
            'motif_depense.required' => 'Le motif de la dépense est obligatoire.',
            'montant.min'            => 'Le montant doit être supérieur à 0.',
            'beneficiaire.required'  => 'Le bénéficiaire est obligatoire.',
            'justificatif_demande.max' => 'Le justificatif ne doit pas dépasser 2 Mo.',
        ];
    }
}