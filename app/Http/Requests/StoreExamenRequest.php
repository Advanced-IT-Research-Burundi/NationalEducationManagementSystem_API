<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:examens,code',
            'libelle' => 'required|string|max:255',
            'niveau_id' => 'required|exists:niveaux_scolaires,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'type' => 'required|in:national,provincial',
        ];
    }
}
