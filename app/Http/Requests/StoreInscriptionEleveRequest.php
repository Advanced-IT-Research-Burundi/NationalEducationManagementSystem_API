<?php

namespace App\Http\Requests;

use App\Models\InscriptionEleve;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInscriptionEleveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', InscriptionEleve::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'exists:eleves,id'],
            'classe_id' => ['required', 'exists:classes,id'],
            'annee_scolaire' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'date_inscription' => ['required', 'date'],
            'statut' => ['nullable', Rule::in(['ACTIVE', 'TRANSFEREE', 'TERMINEE', 'ANNULEE'])],
            'numero_ordre' => ['nullable', 'integer', 'min:1'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'eleve_id.required' => 'L\'élève est requis.',
            'eleve_id.exists' => 'L\'élève sélectionné n\'existe pas.',
            'classe_id.required' => 'La classe est requise.',
            'classe_id.exists' => 'La classe sélectionnée n\'existe pas.',
            'annee_scolaire.required' => 'L\'année scolaire est requise.',
            'annee_scolaire.regex' => 'Le format de l\'année scolaire doit être AAAA-AAAA (ex: 2025-2026).',
            'date_inscription.required' => 'La date d\'inscription est requise.',
        ];
    }
}
