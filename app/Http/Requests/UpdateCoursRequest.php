<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'categorie_cours_id' => ['nullable', 'exists:categories_cours,id'],
            'est_principale' => ['sometimes', 'boolean'],
            'ponderation_tj' => ['sometimes', 'numeric', 'min:0'],
            'ponderation_examen' => ['sometimes', 'numeric', 'min:0'],
            'credit_heures' => ['nullable', 'numeric', 'min:0'],    
            'section_id' => ['nullable', 'exists:sections,id'],
            'section_ids' => ['nullable', 'array'],
            'section_ids.*' => ['exists:sections,id'],
            'niveau_id' => ['nullable', 'exists:niveaux_scolaires,id'],
            'niveau_ids' => ['nullable', 'array'],
            'niveau_ids.*' => ['exists:niveaux_scolaires,id'],
            'description' => ['nullable', 'string'],
            'coefficient' => ['nullable', 'integer', 'min:1'],
            'heures_par_semaine' => ['nullable', 'integer', 'min:0'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}
