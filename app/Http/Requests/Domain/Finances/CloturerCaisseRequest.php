<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class CloturerCaisseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('caisses.cloturer');
    }

    public function rules(): array
    {
        return [
            'solde_compte' => ['required', 'numeric', 'min:0'],
            'motif_ecart'  => ['nullable', 'string', 'min:5', 'max:1000'],
        ];
    }
}