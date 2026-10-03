<?php

namespace App\Http\Requests\Domain\Finances;

use App\Domain\Finances\Types\ResultatRelance;
use App\Domain\Finances\Types\TypeRelance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRelanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recouvrement.relancer');
    }

    public function rules(): array
    {
        return [
            'type_relance'           => ['required', Rule::in(array_keys(TypeRelance::options()))],
            'date_relance'           => ['required', 'date', 'before_or_equal:today'],
            'contenu'                => ['nullable', 'string', 'max:2000'],
            'resultat'               => ['nullable', Rule::in(array_keys(ResultatRelance::options()))],
            'date_prochaine_relance' => ['nullable', 'date', 'after:date_relance'],
        ];
    }
}