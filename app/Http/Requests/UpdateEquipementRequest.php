<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('equipement'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $equipementId = $this->route('equipement')->id ?? null;

        return [
            'salle_id' => ['nullable', 'exists:salles,id'],
            'school_id' => ['sometimes', 'exists:ecoles,id'],
            'nom' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::in(['MOBILIER', 'INFORMATIQUE', 'LABORATOIRE', 'SPORT', 'AUTRE'])],
            'marque' => ['nullable', 'string', 'max:100'],
            'modele' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:100', Rule::unique('equipements', 'numero_serie')->ignore($equipementId)],
            'date_acquisition' => ['nullable', 'date', 'before_or_equal:today'],
            'etat' => ['sometimes', Rule::in(['NEUF', 'BON', 'MOYEN', 'MAUVAIS', 'HORS_SERVICE'])],
            'valeur' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'salle_id.exists' => 'La salle sélectionnée n\'existe pas.',
            'school_id.exists' => 'L\'école sélectionnée n\'existe pas.',
            'type.in' => 'Le type doit être MOBILIER, INFORMATIQUE, LABORATOIRE, SPORT ou AUTRE.',
            'numero_serie.unique' => 'Ce numéro de série existe déjà.',
            'date_acquisition.before_or_equal' => 'La date d\'acquisition ne peut pas être dans le futur.',
            'etat.in' => 'L\'état doit être NEUF, BON, MOYEN, MAUVAIS ou HORS_SERVICE.',
            'valeur.min' => 'La valeur doit être positive.',
        ];
    }
}
