<?php

namespace App\Http\Requests\Domain\Finances;

use App\Domain\Finances\Types\ChequeStatut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RapprocherChequeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cheques.rapprocher');
    }

    public function rules(): array
    {
        return [
            'statut'             => ['required', Rule::in([
                ChequeStatut::ENCAISSE->value,
                ChequeStatut::REJETE->value,
            ])],
            'date_encaissement'  => ['nullable', 'date'],
            'motif_rejet'        => ['required_if:statut,' . ChequeStatut::REJETE->value, 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif_rejet.required_if' => "Le motif de rejet est obligatoire lorsqu'un chèque est rejeté.",
        ];
    }
}