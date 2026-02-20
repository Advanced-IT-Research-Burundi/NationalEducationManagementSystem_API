<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => 'required|exists:schools,id',
            'inspecteur_id' => 'required|exists:users,id',
            'date_prevue' => 'required|date|after_or_equal:today',
            'type' => 'required|in:reguliere,inopinee,thematique',
            'statut' => 'sometimes|in:planifiee,en_cours,terminee,annulee',
        ];
    }
}
