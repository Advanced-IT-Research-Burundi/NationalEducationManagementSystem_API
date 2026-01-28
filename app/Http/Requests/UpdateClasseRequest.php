<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClasseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $classe = $this->route('classe');

        return $this->user()->can('update', $classe);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'niveau_id' => ['sometimes', 'exists:niveaux,id'],
            'annee_scolaire' => ['sometimes', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'local' => ['nullable', 'string', 'max:50'],
            'capacite' => ['nullable', 'integer', 'min:1', 'max:200'],
            'statut' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE', 'ARCHIVEE'])],
        ];
    }
}
