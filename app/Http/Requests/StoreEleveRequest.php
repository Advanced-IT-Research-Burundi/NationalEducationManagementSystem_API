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
            'nationalite' => ['nullable', 'string', 'max:50'],
            'province_origine_id' => ['nullable', 'exists:provinces,id'],
            'commune_origine_id' => ['nullable', 'exists:communes,id'],
            'zone_origine_id' => ['nullable', 'exists:zones,id'],
            'colline_origine_id' => ['nullable', 'exists:collines,id'],
            'niveau_id' => ['nullable', 'exists:niveaux_scolaires,id'],
            'nom_pere' => ['nullable', 'string', 'max:150'],
            'nom_mere' => ['nullable', 'string', 'max:150'],
            'nom_tuteur' => ['nullable', 'string', 'max:200'],
            'contact_tuteur' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'est_orphelin' => ['nullable', 'boolean'],
            'a_handicap' => ['nullable', 'boolean'],
            'type_handicap' => ['nullable', 'string', 'max:100'],
            'school_id' => ['required', 'exists:schools,id'],
            'statut' => ['nullable', Rule::in(['INSCRIT', 'ACTIF', 'SUSPENDU', 'TRANSFERE', 'DIPLOME', 'ABANDONNE'])],

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
            'province_origine_id.exists' => 'La province sélectionnée n\'existe pas.',
            'commune_origine_id.exists' => 'La commune sélectionnée n\'existe pas.',
            'zone_origine_id.exists' => 'La zone sélectionnée n\'existe pas.',
            'colline_origine_id.exists' => 'La colline sélectionnée n\'existe pas.',
            'niveau_id.exists' => 'Le niveau scolaire sélectionné n\'existe pas.',
            'school_id.required' => 'L\'école est requise.',
            'school_id.exists' => 'L\'école sélectionnée n\'existe pas.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'lieu_naissance.required' => 'Le lieu de naissance est requis.',
            'lieu_naissance.max' => 'Le lieu de naissance ne peut pas dépasser 150 caractères.',
            'annee_scolaire.regex' => 'Le format de l\'année scolaire doit être AAAA-AAAA (ex: 2025-2026).',
        ];
    }
}
