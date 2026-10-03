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
            'justificatif'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'nature_analytique' => ['nullable', 'string', 'max:50'],
        ];
    }
}