<?php

namespace App\Http\Requests\Domain\Finances;

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
        return [
            'libelle'            => ['required', 'string', 'max:150'],
            'montant'            => ['required', 'numeric', 'min:0.01'],
            'type_paiement'      => ['required', Rule::in(array_keys(TypeDetail::options()))],
            'modalite_paiement'  => ['required', Rule::in(array_keys(TypePaiement::options()))],
            'type_forfait'       => ['required', Rule::in(array_keys(TypeForfait::options()))],
            'niveau_id'          => ['nullable', 'exists:niveaux,id'],
            'plan_echeancier_id' => ['nullable', 'exists:plan_echeanciers,id'],
        ];
    }
}