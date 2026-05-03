<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaiementRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'statut_paiement' => 'sometimes|in:0,1,2', // 0=en attente, 1=encaissé, 2=annulé
            'motif_suppression' => 'required_if:statut_paiement,2|nullable|string',
        ];
    }
}