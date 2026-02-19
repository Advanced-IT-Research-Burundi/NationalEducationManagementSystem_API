<?php

namespace App\Http\Requests;

use App\Models\Eleve;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEleveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Eleve::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'matricule' => ['nullable', 'string', 'max:50', 'unique:eleves,matricule'],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['required', 'string', 'max:150'],
            'sexe' => ['required', Rule::in(['M', 'F'])],
            'nom_pere' => ['nullable', 'string', 'max:100'],
            'nom_mere' => ['nullable', 'string', 'max:100'],
            'contact_parent' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'school_id' => ['required', 'exists:schools,id'],
            'statut' => ['nullable', Rule::in(['INSCRIT', 'ACTIF', 'SUSPENDU', 'TRANSFERE', 'DIPLOME', 'ABANDONNE'])],

            // Optional: auto-enroll in a class
            'classe_id' => ['nullable', 'exists:classes,id'],
            'annee_scolaire' => ['required_with:classe_id', 'string', 'regex:/^\d{4}-\d{4}$/'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'matricule.required' => 'Le matricule est requis.',
            'matricule.unique' => 'Ce matricule existe déjà.',
            'nom.required' => 'Le nom est requis.',
            'prenom.required' => 'Le prénom est requis.',
            'sexe.required' => 'Le sexe est requis.',
            'sexe.in' => 'Le sexe doit être M ou F.',
            'school_id.required' => 'L\'école est requise.',
            'school_id.exists' => 'L\'école sélectionnée n\'existe pas.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'lieu_naissance.required' => 'Le lieu de naissance est requis.',
            'lieu_naissance.max' => 'Le lieu de naissance ne peut pas dépasser 150 caractères.',
            'annee_scolaire.regex' => 'Le format de l\'année scolaire doit être AAAA-AAAA (ex: 2025-2026).',
        ];
    }
}
