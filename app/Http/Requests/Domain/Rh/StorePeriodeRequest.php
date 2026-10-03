<?php

namespace App\Http\Requests\Domain\Rh;

use Illuminate\Foundation\Http\FormRequest;

class StorePeriodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('paie.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'    => ['required', 'string', 'max:100'],
            'date_debut' => ['required', 'date'],
            'date_fin'   => ['required', 'date', 'after:date_debut'],
        ];
    }
}