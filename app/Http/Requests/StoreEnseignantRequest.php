<?php

namespace App\Http\Requests;

use App\Models\Enseignant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnseignantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Enseignant::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // User creation fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],

            // Enseignant specific fields
            'school_id' => ['nullable', 'exists:schools,id'],
            'matricule' => ['required', 'string', 'max:50', 'unique:enseignants,matricule'],
            'specialite' => ['nullable', 'string', 'max:100'],
            'qualification' => ['nullable', Rule::in(['LICENCE', 'MASTER', 'DOCTORAT', 'DIPLOME_PEDAGOGIQUE', 'AUTRE'])],
            'annees_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'date_embauche' => ['nullable', 'date'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'statut' => ['nullable', Rule::in(['ACTIF', 'INACTIF', 'CONGE', 'SUSPENDU', 'RETRAITE'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est requis.',
            'email.required' => 'L\'email est requis.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'school_id.nullable' => 'L\'école est requise.',
            'matricule.required' => 'Le matricule est requis.',
            'matricule.unique' => 'Ce matricule existe déjà.',
        ];
    }
}
