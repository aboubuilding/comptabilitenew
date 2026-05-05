<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InscriptionBusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscription_id'   => 'required|exists:inscriptions,id',
            'date_debut'       => 'required|date',
            'montant_mensuel'  => 'required|numeric|min:0',
        ];
    }
}