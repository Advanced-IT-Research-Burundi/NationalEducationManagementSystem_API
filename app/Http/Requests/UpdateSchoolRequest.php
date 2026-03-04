<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');
        return $this->user()->can('update', $school);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $schoolId = $this->route('school') ? $this->route('school')->id : null;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code_ecole' => ['nullable', 'string', 'max:50', Rule::unique('schools', 'code_ecole')->ignore($schoolId)],
            'type_ecole' => ['sometimes', Rule::in(['PUBLIQUE', 'PRIVEE', 'ECC', 'AUTRE'])],
            'niveau' => ['sometimes', Rule::in(['FONDAMENTAL', 'POST_FONDAMENTAL', 'SECONDAIRE', 'SUPERIEUR'])],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'directeur_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'capacite_accueil' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'adresse_physique' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'site_web' => ['sometimes', 'nullable', 'url', 'max:255'],
            'annee_creation' => ['sometimes', 'nullable', 'string', 'max:4'],
            'colline_id' => ['sometimes', 'exists:collines,id'],
            'statut' => ['sometimes', Rule::in(['BROUILLON', 'EN_ATTENTE_VALIDATION', 'ACTIVE', 'INACTIVE'])], // Only certain roles should change this via status update but keep flexible for now
            'niveau_scolaire_ids' => ['nullable', 'array'],
            'niveau_scolaire_ids.*' => ['exists:niveaux_scolaires,id'],
        ];
    }
}
