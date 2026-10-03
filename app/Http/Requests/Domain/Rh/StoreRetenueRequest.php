<?php

namespace App\Http\Requests\Domain\Rh;

use Illuminate\Foundation\Http\FormRequest;

class StoreRetenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('paie.gerer');
    }

    public function rules(): array
    {
        return [
            'employe_id' => ['required', 'exists:employes,id'],
            'irpp'       => ['required', 'numeric', 'min:0'],
        ];
    }
}