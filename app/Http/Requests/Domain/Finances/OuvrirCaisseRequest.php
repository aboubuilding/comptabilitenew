<?php

namespace App\Http\Requests\Domain\Finances;

use Illuminate\Foundation\Http\FormRequest;

class OuvrirCaisseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('caisses.ouvrir');
    }

    /**
     * Seuls l'Administrateur (et le superadmin, qui en a le profil) confient
     * une caisse à quelqu'un d'autre ; tout autre utilisateur ouvre la sienne.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->user()->isAdmin()) {
            $this->merge(['responsable_id' => $this->user()->id]);
        }
    }

    public function rules(): array
    {
        return [
            'libelle'        => ['required', 'string', 'max:100'],
            // Les comptes internes sont dans users (utilisateurs est la table historique).
            'responsable_id' => ['required', \Illuminate\Validation\Rule::exists('users', 'id')->where('etat', 1)],
            'solde_initial'  => ['required', 'numeric', 'min:0'],
            // annee_id : injecté par BaseRepository via AnneeScolaireContext.
        ];
    }

    public function messages(): array
    {
        return [
            'responsable_id.required' => 'Choisissez le responsable de la caisse.',
            'responsable_id.exists'   => 'Le responsable sélectionné est introuvable ou inactif.',
        ];
    }
}