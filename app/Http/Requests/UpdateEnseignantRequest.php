<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnseignantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $enseignant = $this->route('enseignant');

        return $this->user()->can('update', $enseignant);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $enseignant = $this->route('enseignant');

        return [
            // User update fields (optional)
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($enseignant->user_id)],

            // Enseignant specific fields
            'matricule' => ['sometimes', 'string', 'max:50', Rule::unique('enseignants', 'matricule')->ignore($enseignant->id)],
            'specialite' => ['nullable', 'string', 'max:100'],
            'qualification' => ['nullable', Rule::in(['LICENCE', 'MASTER', 'DOCTORAT', 'DIPLOME_PEDAGOGIQUE', 'AUTRE'])],
            'annees_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'date_embauche' => ['nullable', 'date'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'statut' => ['sometimes', Rule::in(['ACTIF', 'INACTIF', 'CONGE', 'SUSPENDU', 'RETRAITE'])],
        ];
    }
}
