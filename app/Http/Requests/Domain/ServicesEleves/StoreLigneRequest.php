<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StoreLigneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transport.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'      => ['required', 'string', 'max:150'],
            'chauffeur_id' => ['nullable', 'exists:chauffeurs,id'],
            'voiture_id'   => ['nullable', 'exists:voitures,id'],
            'date_debut'   => ['nullable', 'date'],
            'date_fin'     => ['nullable', 'date', 'after_or_equal:date_debut'],
        ];
    }

    public function attributes(): array
    {
        return ['date_debut' => 'début de la période', 'date_fin' => 'fin de la période'];
    }
}
