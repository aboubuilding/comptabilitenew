<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('depenses.creer');
    }

    public function rules(): array
    {
        return [
            'libelle'       => ['required', 'string', 'max:150'],
            'beneficiaire'  => ['required', 'string', 'max:150'],
            'motif_depense' => ['nullable', 'string', 'max:1000'],
            'date_depense'  => ['required', 'date'],
            'montant'       => ['required', 'numeric', 'min:0.01'],
            // Deux sortes de dépenses : hors ligne budgétaire, ou sur une ligne (alors obligatoire)
            'imputation'          => ['nullable', 'in:hors_ligne,ligne'],
            'ligne_budgetaire_id' => ['nullable', 'required_if:imputation,ligne', 'integer', 'exists:previsions,id'],
            'justificatif'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'nature_analytique' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'ligne_budgetaire_id.required_if' => 'Choisissez la ligne budgétaire de cette dépense.',
            'imputation.in'                   => 'Choisissez une dépense hors ligne ou sur ligne budgétaire.',
        ];
    }
}