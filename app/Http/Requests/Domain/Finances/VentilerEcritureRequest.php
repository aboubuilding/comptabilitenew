<?php

namespace App\Http\Requests\Domain\Finances;

use App\Domain\Finances\Types\SensAnalytique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VentilerEcritureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('analytique.ventiler');
    }

    public function rules(): array
    {
        return [
            'source_type'   => ['required', 'string'], // 'depense' | 'mouvement' | ...
            'source_id'     => ['required', 'integer'],
            'sens'          => ['required', Rule::in(array_keys(SensAnalytique::options()))],

            'imputations'                     => ['required', 'array', 'min:1'],
            'imputations.*.centre_analytique_id' => ['required', 'exists:centres_analytiques,id'],
            'imputations.*.montant'           => ['nullable', 'numeric', 'min:0'],
            'imputations.*.pourcentage'       => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}