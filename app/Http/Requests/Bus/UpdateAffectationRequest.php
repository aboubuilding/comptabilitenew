<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAffectationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'chauffeur_id'      => 'nullable|exists:chauffeurs,id',
            'date_fin'          => 'nullable|date',
            'motif'             => 'nullable|string',
        ];
    }
}