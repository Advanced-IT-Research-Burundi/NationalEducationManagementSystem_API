<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypeScolaireRequest extends FormRequest
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
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
