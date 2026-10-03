<?php

namespace App\Http\Requests\Domain\Rh;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('paie.gerer');
    }

    public function rules(): array
    {
        return [
            'employe_id'            => ['required', 'exists:employes,id'],
            'cnss'                  => ['nullable', 'numeric', 'min:0'],
            'charges_familiale'     => ['nullable', 'numeric', 'min:0'],
            'forfait_professionnel' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}