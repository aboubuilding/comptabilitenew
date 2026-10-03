<?php

namespace App\Http\Requests\Domain\Rh;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employes.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle'      => ['required', 'string', 'max:150'],
            'salaire_base' => ['required', 'numeric', 'min:0'],
        ];
    }
}