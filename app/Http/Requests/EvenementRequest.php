<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvenementRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Les règles de validation.
     */
    public function rules(): array
    {
        $evenementId = $this->route('evenement') ? $this->route('evenement')->id : null;

        return [
            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('evenements', 'nom')->ignore($evenementId)
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['excursion', 'voyage', 'sortie_pedagogique', 'competition', 'autre'])
            ],
            'date_evenement' => ['required', 'date', 'after_or_equal:today'],
            'participation' => ['required', 'numeric', 'min:0'],
            'capacite' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
            'annee_id' => ['nullable', 'integer', 'exists:annees,id'],
            'etat' => ['nullable', 'integer', Rule::in([0, 1])],
        ];
    }

    /**
     * Messages de validation personnalisés.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de l\'événement est obligatoire.',
            'nom.unique' => 'Ce nom d\'événement est déjà utilisé.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'type.required' => 'Le type d\'événement est obligatoire.',
            'type.in' => 'Le type sélectionné est invalide.',
            'date_evenement.required' => 'La date de l\'événement est obligatoire.',
            'date_evenement.date' => 'La date doit être une date valide.',
            'date_evenement.after_or_equal' => 'La date doit être aujourd\'hui ou dans le futur.',
            'participation.required' => 'Le montant de participation est obligatoire.',
            'participation.numeric' => 'Le montant doit être un nombre.',
            'participation.min' => 'Le montant doit être supérieur ou égal à 0.',
            'capacite.integer' => 'La capacité doit être un nombre entier.',
            'capacite.min' => 'La capacité doit être au minimum 1.',
            'description.max' => 'La description ne doit pas dépasser 500 caractères.',
            'annee_id.exists' => 'L\'année sélectionnée est invalide.',
        ];
    }

    /**
     * Préparer les données pour la validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nom' => trim($this->nom ?? ''),
            'description' => trim($this->description ?? ''),
        ]);
    }

    /**
     * Obtenir les données validées avec valeurs par défaut.
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();
        $data['etat'] = $data['etat'] ?? 1;
        return $data;
    }
}
