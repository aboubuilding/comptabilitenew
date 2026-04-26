<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'libelle'      => ['required', 'string', 'max:255'],
            'montant'      => ['required', 'numeric', 'min:0.01'],
            'motif'        => ['required', 'string', 'max:500'],
            'beneficiaire' => ['nullable', 'string', 'max:255'],
            'annee_id'     => ['required', 'exists:annees_scolaires,id'],
            'justificatif' => ['nullable', 'file', 'mimes:pdf,jpg,png,jpeg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'montant.min' => 'Le montant doit être supérieur à 0.',
            'justificatif.max' => 'Le justificatif ne doit pas dépasser 2 Mo.',
        ];
    }
}