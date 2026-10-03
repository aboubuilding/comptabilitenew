<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class EncaisserDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('encaissements.creer');
    }

    public function rules(): array
    {
        return [
            'caisse_id' => ['required', 'exists:caisses,id'],
        ];
    }
}