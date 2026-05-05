<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjusterStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantite' => 'required|integer|min:0',
            'motif'    => 'required|string|max:255',
        ];
    }
}