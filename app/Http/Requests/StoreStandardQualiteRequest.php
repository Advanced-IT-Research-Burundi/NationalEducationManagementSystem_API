<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStandardQualiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:standards_qualite,code',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'criteres' => 'nullable|array',
            'poids' => 'sometimes|integer|min:1',
        ];
    }
}
