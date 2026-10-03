<?php

namespace App\Http\Requests\Domain\Finances;

use App\Domain\Finances\Types\TypePrevision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('previsions.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'              => ['required', 'string', 'max:150'],
            'type'                 => ['required', Rule::in(array_keys(TypePrevision::options()))],
            'montant_previsionnel' => ['required', 'numeric', 'min:0.01'],
            'centre_analytique_id' => ['nullable', 'exists:centres_analytiques,id'],
            'date_debut'           => ['nullable', 'date'],
            'date_fin'             => ['nullable', 'date', 'after_or_equal:date_debut'],
        ];
    }
}