<?php

namespace App\Http\Requests\Domain\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest unique pour la création / modification d'un message.
 */
class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Les adresses libres sont saisies dans une zone de texte (une par ligne,
     * ou séparées par des virgules) : on en fait la liste attendue par le service.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('emails_libres_text')) {
            return;
        }

        $adresses = preg_split('/[\s,;]+/', (string) $this->input('emails_libres_text'), -1, PREG_SPLIT_NO_EMPTY);

        $this->merge(['emails_libres' => array_values(array_unique(array_map('trim', $adresses)))]);
    }

    public function rules(): array
    {
        return [
            'sujet'          => ['required', 'string', 'max:255'],
            'corps'          => ['required', 'string'],
            'type'           => ['nullable', 'string', 'max:50'],
            'modele_id'      => ['nullable', 'integer', Rule::exists('modeles_messages', 'id')->where('etat', 1)],

            // Ciblage
            'ciblage_type'   => ['required', Rule::in(['parents', 'classe', 'niveau', 'tiers', 'libre'])],
            'parent_ids'     => ['array'],
            'parent_ids.*'   => ['integer'],
            'classe_id'      => ['nullable', 'integer'],
            'niveau_id'      => ['nullable', 'integer'],
            'tiers_ids'      => ['array'],
            'tiers_ids.*'    => ['integer'],
            'emails_libres'  => ['array'],
            'emails_libres.*'=> ['email'],
            'emails_libres_text' => ['nullable', 'string'],

            // Envoi
            'envoyer'        => ['nullable', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'sujet.required'          => "Le sujet est obligatoire.",
            'corps.required'          => "Le corps du message est obligatoire.",
            'ciblage_type.required'   => "Le type de ciblage est obligatoire.",
            'ciblage_type.in'         => "Le type de ciblage est invalide.",
            'emails_libres.*.email'   => "Une des adresses libres n'est pas valide.",
            'modele_id.exists'        => "Le modèle sélectionné est introuvable.",
        ];
    }
}