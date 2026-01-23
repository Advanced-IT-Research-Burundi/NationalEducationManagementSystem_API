<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'zone_id' => ['required', 'exists:zones,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la colline est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'zone_id.required' => 'La zone est obligatoire.',
            'zone_id.exists' => 'La zone sélectionnée n\'existe pas.',
        ];
    }
}
