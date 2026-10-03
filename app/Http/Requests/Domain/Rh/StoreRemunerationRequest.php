<?php

namespace App\Http\Requests\Domain\Rh;

use Illuminate\Foundation\Http\FormRequest;

class StoreRemunerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('paie.gerer');
    }

    public function rules(): array
    {
        return [
            'employe_id'        => ['required', 'exists:employes,id'],
            'salaire_base'      => ['required', 'numeric', 'min:0'],
            'prime_anciennete'  => ['nullable', 'numeric', 'min:0'],
            'sursalaire'        => ['nullable', 'numeric', 'min:0'],
            'bonus'             => ['nullable', 'numeric', 'min:0'],
            'logement'          => ['nullable', 'numeric', 'min:0'],
            'nourriture'        => ['nullable', 'numeric', 'min:0'],
            'deplacement'       => ['nullable', 'numeric', 'min:0'],
            'autre_avantage'    => ['nullable', 'numeric', 'min:0'],
        ];
    }
}