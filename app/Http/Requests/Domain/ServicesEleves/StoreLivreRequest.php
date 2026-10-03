<?php

namespace App\Http\Requests\Domain\ServicesEleves;

use Illuminate\Foundation\Http\FormRequest;

class StoreLivreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bibliotheque.gerer');
    }

    public function rules(): array
    {
        return [
            'titre'              => ['required', 'string', 'max:200'],
            'numero'             => ['nullable', 'string', 'max:50'],
            'numero_isbn'        => ['nullable', 'string', 'max:50'],
            'auteur_id'          => ['nullable', 'exists:auteurs,id'],
            'maison_edition_id'  => ['nullable', 'exists:maison_editions,id'],
            'annee_edition_id'   => ['nullable', 'exists:anne_editions,id'],
            'categorie_livre_id' => ['nullable', 'exists:categorie_livres,id'],
            'nombre_exemplaires' => ['required', 'integer', 'min:1', 'max:999'],
            'description'        => ['nullable', 'string', 'max:2000'],
        ];
    }
}