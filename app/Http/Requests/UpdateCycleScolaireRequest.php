<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCycleScolaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_schools');
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type_id' => ['nullable', 'exists:types_scolaires,id'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
