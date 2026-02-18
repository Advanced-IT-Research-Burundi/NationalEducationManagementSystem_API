<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ecole_id' => 'sometimes|exists:ecoles,id',
            'inspecteur_id' => 'sometimes|exists:users,id',
            'date_prevue' => 'sometimes|date',
            'date_realisation' => 'sometimes|nullable|date',
            'type' => 'sometimes|in:reguliere,inopinee,thematique',
            'statut' => 'sometimes|in:planifiee,en_cours,terminee,annulee',
            'rapport' => 'sometimes|nullable|string',
            'note_globale' => 'sometimes|nullable|numeric|min:0|max:100',
        ];
    }
}
