<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class StoreChequeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cheques.gerer');
    }

    public function rules(): array
    {
        return [
            'numero'        => ['required', 'string', 'max:100'],
            'emetteur'      => ['required', 'string', 'max:150'],
            'montant'       => ['required', 'numeric', 'min:0.01'],
            'banque_id'     => ['required', 'exists:banques,id'],
            'date_emission' => ['required', 'date'],
        ];
    }
}