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
            'nom'    => ['sometimes', 'string', 'max:150'],
            'prenom' => ['sometimes', 'string', 'max:150'],
            'email'  => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($enseignant->user_id)],

            // Enseignant specific fields
            'ecoles' => ['sometimes', 'array', 'min:1'],
            'ecoles.*' => ['integer', 'exists:schools,id'],
            'matricule' => ['sometimes', 'string', 'max:50', Rule::unique('enseignants', 'matricule')->ignore($enseignant->id)],
            'date_naissance' => ['nullable', 'date'],
            'qualification' => ['nullable', Rule::in(['LICENCE', 'MASTER', 'DOCTORAT', 'DIPLOME_PEDAGOGIQUE', 'AUTRE'])],
            'qualification_precision' => ['nullable', 'string', 'max:100'],
            'domaines' => ['nullable', 'array'],
            'domaines.*' => ['string', 'max:100', 'distinct'],
            'annees_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'date_embauche' => ['nullable', 'date'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'statut' => ['sometimes', Rule::in(['ACTIF', 'DECEDE', 'TRANSFERE', 'CONGE', 'SUSPENDU', 'RETRAITE'])],
        ];
    }
}
