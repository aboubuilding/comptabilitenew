<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class AbandonnerInscriptionCantineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cantine.gerer');
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}