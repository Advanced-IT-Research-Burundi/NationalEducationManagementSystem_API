<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStandardQualiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'criteres' => 'nullable|array',
            'poids' => 'sometimes|integer|min:1',
        ];
    }
}
