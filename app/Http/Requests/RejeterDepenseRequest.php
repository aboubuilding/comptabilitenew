<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejeterDepenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['motif' => ['required', 'string', 'max:500']];
    }
}