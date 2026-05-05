<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAchatRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'date_achat'         => 'required|date',
            'fournisseur_id'     => 'nullable|exists:fournisseurs,id',
            'nom_acheteur'       => 'nullable|string|max:255',
            'reference'          => 'nullable|string|max:255',
            'bon_commande'       => 'nullable|string|max:255',
            'commentaire'        => 'nullable|string',
            'type_achat'         => 'required|in:1,2', // 1=cantine,2=boutique
            'statut_paiement'    => 'nullable|in:0,1,2',
            'statut_livraison'   => 'nullable|in:0,1,2',
            'produits'           => 'required|array|min:1',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite'   => 'required|numeric|min:0.001',
            'produits.*.prix_unitaire' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'produits.required' => 'Au moins un produit est requis.',
            'produits.*.quantite.min' => 'La quantité doit être positive.',
        ];
    }
}