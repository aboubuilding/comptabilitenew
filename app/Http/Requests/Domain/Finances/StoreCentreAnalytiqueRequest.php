<?php

namespace App\Http\Requests\Domain\Finances;

use App\Domain\Finances\Types\TypeCentreAnalytique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCentreAnalytiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('analytique.gerer');
    }

    public function rules(): array
    {
        $id = $this->route('centre');

        return [
            'code'           => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('centres_analytiques', 'code')->ignore($id)],
            'libelle'        => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'type'           => ['required', Rule::in(array_keys(TypeCentreAnalytique::options()))],
            'responsable_id' => ['nullable', 'exists:users,id'],
        ];
    }
}