<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCycleScolaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_schools');
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type_id' => ['nullable', 'exists:types_scolaires,id'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
