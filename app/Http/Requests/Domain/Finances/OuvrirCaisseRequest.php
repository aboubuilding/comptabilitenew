<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class OuvrirCaisseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('caisses.ouvrir');
    }

    public function rules(): array
    {
        return [
            'libelle'        => ['required', 'string', 'max:100'],
            'responsable_id' => ['required', 'exists:utilisateurs,id'],
            'solde_initial'  => ['required', 'numeric', 'min:0'],
            // annee_id : injecté par BaseRepository via AnneeScolaireContext.
        ];
    }

    public function messages(): array
    {
        return [
            'responsable_id.exists' => 'Le caissier sélectionné est introuvable.',
        ];
    }
}