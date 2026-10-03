<?php

namespace App\Http\Requests\Domain\Logistique;

use Illuminate\Foundation\Http\FormRequest;

class ReceptionnerAchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('achats.receptionner');
    }

    public function rules(): array
    {
        return []; // aucune donnée supplémentaire
    }
}