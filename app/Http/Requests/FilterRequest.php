<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'statut'        => 'nullable|integer|in:1,2,3',
            'date_debut'    => 'nullable|date_format:Y-m-d',
            'date_fin'      => 'nullable|date_format:Y-m-d|after_or_equal:date_debut',
            'annee_id'      => 'nullable|integer|exists:annees_scolaires,id',
            'caissier_id'   => 'nullable|integer|exists:users,id',
            'period'        => 'nullable|in:day,week,month,year',
            'by_caissier'   => 'nullable|boolean',
            'statut_ecart'  => 'nullable|in:aucun,en_attente,valide,refuse,enquete',
            'ecart_min'     => 'nullable|numeric|min:0',
            'type'          => 'nullable|in:all,entree,sortie',
            'search'        => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
            'period.in'               => 'Période invalide (day, week, month, year).',
        ];
    }
}