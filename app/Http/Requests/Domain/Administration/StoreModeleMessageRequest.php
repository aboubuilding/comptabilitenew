<?php

namespace App\Http\Requests\Domain\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModeleMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('modele')?->id;

        return [
            'code'    => ['required', 'string', 'max:50', Rule::unique('modeles_messages', 'code')->ignore($id)],
            'libelle' => ['required', 'string', 'max:150'],
            'sujet'   => ['required', 'string', 'max:255'],
            'corps'   => ['required', 'string'],
            'type'    => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'    => "Le code est obligatoire.",
            'code.unique'      => "Ce code de modèle existe déjà.",
            'libelle.required' => "Le libellé est obligatoire.",
            'sujet.required'   => "Le sujet est obligatoire.",
            'corps.required'   => "Le corps est obligatoire.",
        ];
    }
}