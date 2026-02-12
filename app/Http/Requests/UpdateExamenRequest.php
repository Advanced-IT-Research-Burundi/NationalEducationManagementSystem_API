<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|unique:examens,code,' . $this->route('examen'),
            'libelle' => 'sometimes|string|max:255',
            'niveau_id' => 'sometimes|exists:niveaux_scolaires,id',
            'annee_scolaire_id' => 'sometimes|exists:annee_scolaires,id',
            'type' => 'sometimes|in:national,provincial',
        ];
    }
}
