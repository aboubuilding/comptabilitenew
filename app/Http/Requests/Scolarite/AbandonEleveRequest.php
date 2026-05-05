<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbandonEleveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_abandon'  => 'required|date',
            'motif_abandon' => 'required|string|max:500',
        ];
    }
}