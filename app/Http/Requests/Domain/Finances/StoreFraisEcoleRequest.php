<?php

namespace App\Http\Requests\Domain\Finances;

use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypeForfait;
use App\Domain\Finances\Types\TypePaiement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFraisEcoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('frais.gerer');
    }

    public function rules(): array
    {
        // À la création, seuls l'inscription, l'assurance et la scolarité se définissent ici :
        // cantine, transport, activités et événements relèvent de leurs modules.
        // En modification, la nature d'un frais existant est contrôlée par le service.
        $natures = $this->isMethod('POST')
            ? array_map(fn (TypeDetail $t) => $t->value, FraisEcoleRepository::CREABLES)
            : array_keys(TypeDetail::options());

        return [
            'libelle'            => ['required', 'string', 'max:150'],
            'montant'            => ['required', 'numeric', 'min:0.01'],
            'type_paiement'      => ['required', Rule::in($natures)],
            'modalite_paiement'  => ['required', Rule::in(array_keys(TypePaiement::options()))],
            'type_forfait'       => ['required', Rule::in(array_keys(TypeForfait::options()))],
            'niveau_id'          => ['nullable', 'exists:niveaux,id'],
            'plan_echeancier_id' => ['nullable', 'exists:plan_echeanciers,id'],
            // Échéancier saisi avec le frais (paiement échelonné)
            'echeances'                 => ['nullable', 'array', 'max:24'],
            'echeances.*.libelle'       => ['nullable', 'string', 'max:150'],
            'echeances.*.date_echeance' => ['required', 'date'],
            'echeances.*.montant'       => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_paiement.in'                   => "Seuls les frais d'inscription, d'assurance et de scolarité se créent ici : la cantine, le transport, les activités et les événements se définissent dans leurs modules.",
            'echeances.*.date_echeance.required' => "Chaque échéance doit avoir une date.",
            'echeances.*.montant.required'       => "Chaque échéance doit avoir un montant.",
        ];
    }
}
