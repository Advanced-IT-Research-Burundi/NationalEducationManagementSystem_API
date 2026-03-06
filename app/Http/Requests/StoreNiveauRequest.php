<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class StoreNiveauRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage_schools');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:niveaux_scolaires,code'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'type_id' => ['nullable', 'exists:types_scolaires,id'],
            'cycle_id' => ['nullable', 'exists:cycles_scolaires,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'actif' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du niveau est requis.',
            'code.required' => 'Le code du niveau est requis.',
            'code.unique' => 'Ce code de niveau existe déjà.',
        ];
    }
}
