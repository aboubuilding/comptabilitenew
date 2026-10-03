<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Types\TypeActivite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActiviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('activites.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'           => ['required', 'string', 'max:200'],
            'description'       => ['nullable', 'string', 'max:2000'],
            'montant'           => ['required', 'numeric', 'min:0'],
            'type'              => ['nullable', Rule::in(array_keys(TypeActivite::options()))],
            'encadreur'         => ['nullable', 'string', 'max:150'],
            'contact_encadreur' => ['nullable', 'string', 'max:50'],
            'niveau_id'         => ['nullable', 'exists:niveaux,id'],
        ];
    }
}