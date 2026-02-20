<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'required|date|after_or_equal:today',
            'date_fin' => 'required|date|after:date_debut',
            'formateur_id' => 'required|exists:users,id',
            'lieu' => 'nullable|string',
            'capacite' => 'nullable|integer|min:1',
            'statut' => 'sometimes|in:planifiee,en_cours,terminee,annulee',
        ];
    }
}
