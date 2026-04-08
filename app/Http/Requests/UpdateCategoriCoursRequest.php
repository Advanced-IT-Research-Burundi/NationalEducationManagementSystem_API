<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoriCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'ordre' => ['sometimes', 'integer', 'min:0'],
            'afficher_bulletin' => ['sometimes', 'boolean'],
        ];
    }
}
