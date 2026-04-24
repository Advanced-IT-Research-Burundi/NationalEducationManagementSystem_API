<?php

namespace App\Http\Requests;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('section'));
    }

    public function rules(): array
    {
        $sectionId = $this->route('section')->id;

        return [
            'nom' => ['sometimes', 'required', 'string', 'max:100'],
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:sections,code,' . $sectionId],
            'description' => ['nullable', 'string', 'max:500'],
            'niveau_id' => ['nullable', 'exists:niveaux_scolaires,id'],
            'niveau_ids' => ['nullable', 'array'],
            'niveau_ids.*' => ['exists:niveaux_scolaires,id'],
            'actif' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la section est requis.',
            'code.required' => 'Le code de la section est requis.',
            'code.unique' => 'Ce code de section existe déjà.',
            'niveau_id.exists' => 'Le niveau sélectionné n\'existe pas.',
            'niveau_ids.array' => 'Les niveaux doivent être un tableau.',
            'niveau_ids.*.exists' => 'Un des niveaux sélectionnés n\'existe pas.',
        ];
    }
}
