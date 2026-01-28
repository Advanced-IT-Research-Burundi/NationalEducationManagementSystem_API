<?php

namespace App\Http\Requests;

use App\Models\Classe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClasseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Classe::class);
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
            'code' => ['nullable', 'string', 'max:50'],
            'niveau_id' => ['required', 'exists:niveaux,id'],
            'school_id' => ['required', 'exists:schools,id'],
            'annee_scolaire' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'local' => ['nullable', 'string', 'max:50'],
            'capacite' => ['nullable', 'integer', 'min:1', 'max:200'],
            'statut' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE', 'ARCHIVEE'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la classe est requis.',
            'niveau_id.required' => 'Le niveau est requis.',
            'niveau_id.exists' => 'Le niveau sélectionné n\'existe pas.',
            'school_id.required' => 'L\'école est requise.',
            'school_id.exists' => 'L\'école sélectionnée n\'existe pas.',
            'annee_scolaire.required' => 'L\'année scolaire est requise.',
            'annee_scolaire.regex' => 'Le format de l\'année scolaire doit être AAAA-AAAA (ex: 2025-2026).',
        ];
    }
}
