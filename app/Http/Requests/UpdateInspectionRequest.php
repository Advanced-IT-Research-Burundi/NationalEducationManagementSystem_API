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
            'date_realisation' => 'sometimes|date',
            'type' => 'sometimes|in:reguliere,inopinee,thematique',
            'statut' => 'sometimes|in:planifiee,en_cours,terminee,annulee',
            'rapport' => 'sometimes|string',
            'note_globale' => 'sometimes|numeric|min:0|max:100',
        ];
    }
}
