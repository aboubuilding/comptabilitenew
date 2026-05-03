<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'libelle'      => 'required|string|max:255',
            'emplacement'  => 'nullable|string|max:255',
            'cycle_id'     => 'required|integer|exists:cycles,id',
            'niveau_id'    => 'required|integer|exists:niveaux,id',
           
        ];
    }
}