<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StorePreparationRepasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cantine.gerer');
    }

    public function rules(): array
    {
        return [
            'menu_id'          => ['required', 'exists:menus,id'],
            'magasin_id'       => ['required', 'exists:magasins,id'],
            'date_preparation' => ['required', 'date'],
            'nombre_parts'     => ['required', 'integer', 'min:1'],
            'cout_reel'        => ['nullable', 'numeric', 'min:0'],
            'observations'     => ['nullable', 'string', 'max:2000'],
        ];
    }
}