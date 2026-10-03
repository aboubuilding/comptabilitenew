<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class RejeterDepenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('depenses.valider');
    }

    public function rules(): array
    {
        return [
            'motif_rejet' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}