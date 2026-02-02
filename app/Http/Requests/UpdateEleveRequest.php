<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEleveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $eleve = $this->route('eleve');

        return $this->user()->can('update', $eleve);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $eleve = $this->route('eleve');

        return [
            'matricule' => ['sometimes', 'string', 'max:50', Rule::unique('eleves', 'matricule')->ignore($eleve->id)],
            'nom' => ['sometimes', 'string', 'max:100'],
            'prenom' => ['sometimes', 'string', 'max:100'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:100'],
            'sexe' => ['sometimes', Rule::in(['M', 'F'])],
            'nom_pere' => ['nullable', 'string', 'max:100'],
            'nom_mere' => ['nullable', 'string', 'max:100'],
            'contact_parent' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'statut' => ['sometimes', Rule::in(['INSCRIT', 'ACTIF', 'SUSPENDU', 'TRANSFERE', 'DIPLOME', 'ABANDONNE'])],
        ];
    }
}
