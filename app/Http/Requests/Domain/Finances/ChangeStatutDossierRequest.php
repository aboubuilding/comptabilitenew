<?php

namespace App\Http\Requests\Domain\Finances;

use App\Domain\Finances\Types\DossierStatut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatutDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recouvrement.gerer');
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::in(array_keys(DossierStatut::options()))],
            'motif'  => ['nullable', 'string', 'max:500'],
        ];
    }
}