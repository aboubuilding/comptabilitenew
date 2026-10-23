<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transport.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'     => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'ligne_id'    => ['nullable', 'exists:lignes,id'],
            'tarif_base'  => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['tarif_base' => 'tarif indicatif', 'ligne_id' => 'ligne'];
    }
}
