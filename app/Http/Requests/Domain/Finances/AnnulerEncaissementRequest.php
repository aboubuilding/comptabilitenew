<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerEncaissementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('encaissements.annuler');
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}