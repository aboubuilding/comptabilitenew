<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class ReglerDepenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('depenses.regler');
    }

    public function rules(): array
    {
        return [
            'caisse_id' => ['required', 'exists:caisses,id'],
        ];
    }
}