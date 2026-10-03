<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerPaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('paiements.annuler');
    }

    public function rules(): array
    {
        return [
            'motif_suppression' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}