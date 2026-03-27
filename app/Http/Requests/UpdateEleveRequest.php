<?php

namespace App\Http\Requests;

use App\Models\Eleve;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEleveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $eleve = $this->resolveEleve();
        $eleveId = $eleve?->id ?? $this->route('eleve');

        return [
            'matricule' => ['sometimes', 'string', 'max:50', Rule::unique('eleves', 'matricule')->ignore($eleveId)],
            'nom' => ['sometimes', 'string', 'max:100'],
            'prenom' => ['sometimes', 'string', 'max:100'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['sometimes', 'string', 'max:150'],
            'sexe' => ['sometimes', Rule::in(['M', 'F'])],
            'nationalite' => ['nullable', 'string', 'max:50'],
            'province_origine_id' => ['nullable', 'exists:provinces,id'],
            'commune_origine_id' => ['nullable', 'exists:communes,id'],
            'zone_origine_id' => ['nullable', 'exists:zones,id'],
            'colline_origine_id' => ['nullable', 'exists:collines,id'],
            'niveau_id' => ['nullable', 'exists:niveaux_scolaires,id'],
            'nom_pere' => ['nullable', 'string', 'max:200'],
            'nom_mere' => ['nullable', 'string', 'max:200'],
            'nom_tuteur' => ['nullable', 'string', 'max:200'],
            'contact_tuteur' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'statut' => ['sometimes', Rule::in(['INSCRIT', 'ACTIF', 'SUSPENDU', 'TRANSFERE', 'DIPLOME', 'ABANDONNE'])],
        ];
    }

    private function resolveEleve(): ?Eleve
    {
        $param = $this->route('eleve');

        if ($param instanceof Eleve) {
            return $param;
        }

        return $param ? Eleve::find($param) : null;
    }
}
