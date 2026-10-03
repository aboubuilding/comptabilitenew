<?php

namespace App\Http\Requests\Domain\Rh;

use Illuminate\Foundation\Http\FormRequest;

class StorePaieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('paie.gerer');
    }

    public function rules(): array
    {
        return [
            'employe_id' => ['required', 'exists:employes,id'],
            'periode_id' => ['required', 'exists:periodes,id'],
        ];
    }
}