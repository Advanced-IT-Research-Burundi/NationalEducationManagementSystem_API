<?php

namespace App\Http\Requests;

use App\Models\Equipement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Equipement::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'salle_id' => ['nullable', 'exists:salles,id'],
            'ecole_id' => ['required', 'exists:ecoles,id'],
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['MOBILIER', 'INFORMATIQUE', 'LABORATOIRE', 'SPORT', 'AUTRE'])],
            'marque' => ['nullable', 'string', 'max:100'],
            'modele' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:100', 'unique:equipements,numero_serie'],
            'date_acquisition' => ['nullable', 'date', 'before_or_equal:today'],
            'etat' => ['required', Rule::in(['NEUF', 'BON', 'MOYEN', 'MAUVAIS', 'HORS_SERVICE'])],
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
            'ecole_id.required' => 'L\'école est requise.',
            'ecole_id.exists' => 'L\'école sélectionnée n\'existe pas.',
            'nom.required' => 'Le nom de l\'équipement est requis.',
            'type.required' => 'Le type d\'équipement est requis.',
            'type.in' => 'Le type doit être MOBILIER, INFORMATIQUE, LABORATOIRE, SPORT ou AUTRE.',
            'numero_serie.unique' => 'Ce numéro de série existe déjà.',
            'date_acquisition.before_or_equal' => 'La date d\'acquisition ne peut pas être dans le futur.',
            'etat.required' => 'L\'état de l\'équipement est requis.',
            'etat.in' => 'L\'état doit être NEUF, BON, MOYEN, MAUVAIS ou HORS_SERVICE.',
            'valeur.min' => 'La valeur doit être positive.',
        ];
    }
}
