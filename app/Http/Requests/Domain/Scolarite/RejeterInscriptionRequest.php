<?php

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;

class RejeterInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Direction seule
    }

    public function rules(): array
    {
        return ['motif' => ['required', 'string', 'min:5']];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de rejet est obligatoire.',
            'motif.min'      => 'Le motif doit être un peu plus détaillé (5 caractères minimum).',
        ];
    }
}