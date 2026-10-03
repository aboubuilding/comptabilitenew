<?php

namespace App\Http\Requests\Domain\Logistique;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerAchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('achats.gerer');
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}