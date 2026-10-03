<?php

namespace App\Http\Requests\Domain\Scolarite;

use App\Domain\Scolarite\Types\StatutValidationInscription;
use App\Support\AnneeScolaireContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class InscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO : Policy — Secrétariat (saisie), cf. cahier des charges §4 (1.3)
    }

    public function rules(): array
    {
        return [
            'eleve_id'          => ['required', 'integer', 'exists:eleves,id'],
            'cycle_id'          => ['required', 'integer', 'exists:cycles,id'],
            'niveau_id'         => ['required', 'integer', 'exists:niveaux,id'],
            'classe_id'         => ['nullable', 'integer', 'exists:classes,id'],
            'type_inscription'  => ['required', 'integer', 'in:1,2'],
            'date_inscription'  => ['required', 'date'],
            'taux_remise'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'frais_scolarite'   => ['nullable', 'numeric', 'min:0'],
            'frais_inscription' => ['nullable', 'numeric', 'min:0'],
            'frais_assurance'   => ['nullable', 'numeric', 'min:0'],
            'frais_examen'      => ['nullable', 'numeric', 'min:0'],
            'frais_cantine'     => ['nullable', 'numeric', 'min:0'],
            'frais_bus'         => ['nullable', 'numeric', 'min:0'],
            'frais_livre'       => ['nullable', 'numeric', 'min:0'],
            'remise_scolarite'  => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Un élève n'a qu'une inscription en cours par année scolaire : un
     * dossier rejeté ou abandonné n'empêche pas d'en saisir un nouveau.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('eleve_id')) {
                return;
            }

            $dejaInscrit = DB::table('inscriptions')
                ->where('eleve_id', $this->integer('eleve_id'))
                ->where('annee_id', app(AnneeScolaireContext::class)->id())
                ->where('etat', 1)
                ->where('statut_abandon', 0)
                ->where('statut_validation', '!=', StatutValidationInscription::REJETEE->value)
                ->exists();

            if ($dejaInscrit) {
                $validator->errors()->add('eleve_id', 'Cet élève a déjà une inscription en cours pour cette année scolaire.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'eleve_id.required'  => "L'élève est obligatoire.",
            'cycle_id.required'  => 'Le cycle est obligatoire.',
            'niveau_id.required' => 'Le niveau est obligatoire.',
            'type_inscription.required' => "Le type d'inscription est obligatoire.",
            'date_inscription.required' => "La date d'inscription est obligatoire.",
        ];
    }

    public function attributes(): array
    {
        return [
            'eleve_id' => 'élève', 'cycle_id' => 'cycle', 'niveau_id' => 'niveau', 'classe_id' => 'classe',
            'type_inscription' => "type d'inscription", 'date_inscription' => "date d'inscription",
            'taux_remise' => 'taux de remise',
        ];
    }
}