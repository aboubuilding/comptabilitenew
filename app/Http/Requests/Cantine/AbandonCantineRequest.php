<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbandonCantineRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'motif' => 'required|string|max:500',
        ];
    }
}