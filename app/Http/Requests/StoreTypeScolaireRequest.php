<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeScolaireRequest extends FormRequest
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
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
