<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoriCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'ordre' => ['required', 'integer', 'min:0'],
            'afficher_bulletin' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la catégorie est obligatoire.',
            'ordre.required' => "L'ordre est obligatoire.",
            'ordre.integer' => "L'ordre doit être un nombre entier.",
        ];
    }
}
