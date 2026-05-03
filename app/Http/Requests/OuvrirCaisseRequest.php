<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OuvrirCaisseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'solde_initial' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'solde_initial.required' => 'Le fond de caisse initial est obligatoire.',
            'solde_initial.numeric'  => 'Le fond de caisse doit être un nombre.',
            'solde_initial.min'      => 'Le fond de caisse ne peut pas être négatif.',
        ];
    }
}