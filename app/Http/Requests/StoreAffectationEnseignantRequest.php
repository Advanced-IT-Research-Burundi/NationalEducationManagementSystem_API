<?php

namespace App\Http\Requests;

use App\Models\AffectationEnseignant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAffectationEnseignantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', AffectationEnseignant::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'enseignant_id' => ['required', 'exists:enseignants,id'],
            'classe_id' => ['required', 'exists:classes,id'],
            'matiere' => ['nullable', 'string', 'max:100'],
            'est_titulaire' => ['nullable', 'boolean'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
            'statut' => ['nullable', Rule::in(['ACTIVE', 'TERMINEE', 'ANNULEE'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'enseignant_id.required' => 'L\'enseignant est requis.',
            'enseignant_id.exists' => 'L\'enseignant sélectionné n\'existe pas.',
            'classe_id.required' => 'La classe est requise.',
            'classe_id.exists' => 'La classe sélectionnée n\'existe pas.',
            'date_debut.required' => 'La date de début est requise.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}
