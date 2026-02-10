<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after:date_debut',
            'formateur_id' => 'sometimes|exists:users,id',
            'lieu' => 'nullable|string',
            'capacite' => 'nullable|integer|min:1',
            'statut' => 'sometimes|in:planifiee,en_cours,terminee,annulee',
        ];
    }
}
