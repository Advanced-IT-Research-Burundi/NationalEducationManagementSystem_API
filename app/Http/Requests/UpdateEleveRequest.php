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
            'lieu_naissance' => ['sometimes', 'string', 'max:150'],
            'sexe' => ['sometimes', Rule::in(['M', 'F'])],
            'nom_pere' => ['nullable', 'string', 'max:100'],
            'nom_mere' => ['nullable', 'string', 'max:100'],
            'contact_parent' => ['nullable', 'string', 'max:20'],
            'contact_tuteur' => ['nullable', 'string', 'max:255'],
            'nom_tuteur' => ['nullable', 'string', 'max:200'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'niveau_id' => ['nullable', 'exists:niveaux_scolaires,id'],
            'nationalite' => ['nullable', 'string', 'max:50'],
            'school_id' => ['sometimes', 'exists:schools,id'],
            'province_origine_id' => ['nullable', 'integer'],
            'commune_origine_id' => ['nullable', 'integer'],
            'zone_origine_id' => ['nullable', 'integer'],
            'colline_origine_id' => ['nullable', 'exists:collines,id'],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'est_orphelin' => ['nullable', 'boolean'],
            'a_handicap' => ['nullable', 'boolean'],
            'type_handicap' => ['nullable', 'string', 'max:100'],
            'statut' => ['sometimes', Rule::in(['INSCRIT', 'ACTIF', 'SUSPENDU', 'TRANSFERE', 'DIPLOME', 'ABANDONNE'])],
        ];
    }
}
