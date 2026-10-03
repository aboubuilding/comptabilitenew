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
            'libelle'      => ['required', 'string', 'max:150'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'chauffeur_id' => ['nullable', 'exists:chauffeurs,id'],
            'voiture_id'   => ['nullable', 'exists:voitures,id'],
        ];
    }
}