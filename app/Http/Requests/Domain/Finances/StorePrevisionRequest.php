<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

/** Ligne budgétaire : libellé et montant global. */
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
            'montant_previsionnel' => ['required', 'numeric', 'min:0.01'],
            'centre_analytique_id' => ['nullable', 'integer', 'exists:centres_analytiques,id'],
        ];
    }

    public function attributes(): array
    {
        return ['montant_previsionnel' => 'montant global'];
    }
}
