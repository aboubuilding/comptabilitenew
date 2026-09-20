<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBanqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('banques.gerer');
    }

    public function rules(): array
    {
        $id = $this->route('banque');

        return [
            'nom' => [
                'required', 'string', 'max:150',
                Rule::unique('banques', 'nom')->ignore($id),
            ],
        ];
    }
}