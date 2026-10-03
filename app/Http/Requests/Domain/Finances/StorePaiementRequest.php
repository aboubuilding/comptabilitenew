<?php

namespace App\Http\Requests\Domain\Finances;

use App\Domain\Finances\Types\ModePaiement;
use App\Domain\Finances\Types\TypeDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('paiements.creer');
    }

    public function rules(): array
    {
        return [
            'inscription_id'      => ['required', 'exists:inscriptions,id'],
            'payeur'              => ['required', 'string', 'max:150'],
            'telephone_payeur'    => ['nullable', 'string', 'max:30'],
            'mode_paiement'       => ['required', Rule::in(array_keys(ModePaiement::disponibles()))],

            // Paiement par chèque : les références du chèque sont obligatoires
            'cheque'               => ['nullable', 'array'],
            'cheque.numero'        => ['nullable', 'required_if:mode_paiement,' . ModePaiement::CHEQUE->value, 'string', 'max:100'],
            'cheque.banque_id'     => ['nullable', 'required_if:mode_paiement,' . ModePaiement::CHEQUE->value, Rule::exists('banques', 'id')->where('etat', 1)],
            'cheque.emetteur'      => ['nullable', 'string', 'max:150'],
            'cheque.date_emission' => ['nullable', 'date'],

            'lignes'                       => ['required', 'array', 'min:1'],
            'lignes.*.type_paiement'       => ['required', Rule::in(array_keys(TypeDetail::options()))],
            'lignes.*.libelle'             => ['required', 'string', 'max:200'],
            'lignes.*.montant'             => ['required', 'numeric', 'min:0.01'],

            'lignes.*.quantite'            => ['nullable', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire'       => ['nullable', 'numeric', 'min:0'],

            'lignes.*.frais_ecole_id'      => ['nullable', 'exists:frais_ecoles,id'],
            'lignes.*.souscription_id'     => ['nullable', 'integer'],
            // Engagement réglé par la ligne (souscription:12, cantine:3, activite:7…) :
            // sert à vérifier que le montant ne dépasse pas le reste à payer.
            'lignes.*.engagement'          => ['nullable', 'string', 'max:50'],
            'lignes.*.produit_id'          => ['nullable', 'exists:produits,id'],
            'lignes.*.service_id'          => ['nullable', 'integer'],
            'lignes.*.activite_id'         => ['nullable', 'exists:activites,id'],
            'lignes.*.evenement_id'        => ['nullable', 'exists:evenements,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'lignes.required' => 'Le panier est vide.',
            'mode_paiement.in'            => 'Ce mode de paiement n\'est pas disponible pour le moment (espèces ou chèque).',
            'cheque.numero.required_if'   => 'Le numéro du chèque est obligatoire.',
            'cheque.banque_id.required_if' => 'La banque du chèque est obligatoire.',
            'cheque.banque_id.exists'     => 'La banque sélectionnée est introuvable.',
            'lignes.min'      => 'Ajoutez au moins une ligne au panier.',
        ];
    }
}