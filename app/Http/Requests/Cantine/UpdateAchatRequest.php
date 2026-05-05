<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAchatRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'statut_paiement'  => 'nullable|in:0,1,2',
            'statut_livraison' => 'nullable|in:0,1,2',
            'commentaire'      => 'nullable|string',
            // on ne permet pas de modifier les produits via cette requête
        ];
    }
}