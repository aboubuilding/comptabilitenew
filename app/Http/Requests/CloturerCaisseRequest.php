<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloturerCaisseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'solde_physique' => 'required|numeric|min:0',
            'motif_ecart'    => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'solde_physique.required' => 'Le solde physique constaté est obligatoire.',
            'solde_physique.numeric'  => 'Le solde doit être un nombre.',
            'motif_ecart.max'         => 'Le motif ne doit pas dépasser 1000 caractères.',
        ];
    }
}