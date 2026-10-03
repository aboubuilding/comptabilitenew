<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StoreChauffeurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transport.gerer');
    }

    public function rules(): array
    {
        return [
            'nom'                  => ['required', 'string', 'max:100'],
            'prenom'               => ['required', 'string', 'max:100'],
            'permis_conduire'      => ['nullable', 'string', 'max:50'],
            'date_validite_permis' => ['nullable', 'date'],
            'telephone'            => ['nullable', 'string', 'max:30'],
            'email'                => ['nullable', 'email', 'max:150'],
            'adresse'              => ['nullable', 'string', 'max:1000'],
            'statut'               => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}