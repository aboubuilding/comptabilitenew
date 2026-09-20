<?php

namespace App\Http\Requests\Domain\Finances;

use App\Domain\Finances\Types\MouvementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMouvementCaisseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('caisses.mouvements');
    }

    public function rules(): array
    {
        return [
            'libelle'        => ['required', 'string', 'max:150'],
            'type_mouvement' => [
                'required',
                Rule::in(array_map(fn (MouvementType $t) => $t->value, MouvementType::manuels())),
            ],
            'montant'        => ['required', 'numeric', 'min:0.01'],
            'date_mouvement' => ['required', 'date'],
            'beneficiaire'   => ['nullable', 'string', 'max:150'],
            'motif'          => ['nullable', 'string', 'max:1000'],
        ];
    }
}