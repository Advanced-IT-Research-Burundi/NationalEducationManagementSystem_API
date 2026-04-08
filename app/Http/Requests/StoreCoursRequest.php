<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'categorie_cours_id' => ['nullable', 'exists:categories_cours,id'],
            'est_principale' => ['boolean'],
            'ponderation_tj' => ['required', 'numeric', 'min:0'],
            'ponderation_examen' => ['required', 'numeric', 'min:0'],
            'credit_heures' => ['nullable', 'numeric', 'min:0'],
            'enseignant_id' => ['nullable', 'exists:enseignants,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'niveau_id' => ['nullable', 'exists:niveaux_scolaires,id'],
            'description' => ['nullable', 'string'],
            'coefficient' => ['nullable', 'integer', 'min:1'],
            'heures_par_semaine' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du cours est obligatoire.',
            'ponderation_tj.required' => 'La pondération T.J. est obligatoire.',
            'ponderation_examen.required' => "La pondération Examen est obligatoire.",
        ];
    }
}
