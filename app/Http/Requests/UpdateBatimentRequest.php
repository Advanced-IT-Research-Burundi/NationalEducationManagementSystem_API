<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatimentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('batiment'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ecole_id' => ['sometimes', 'exists:ecoles,id'],
            'nom' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::in(['ACADEMIQUE', 'ADMINISTRATIF', 'SPORTIF', 'AUTRE'])],
            'annee_construction' => ['nullable', 'integer', 'min:1800', 'max:'.(date('Y') + 5)],
            'superficie' => ['nullable', 'numeric', 'min:0'],
            'nombre_etages' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'etat' => ['sometimes', Rule::in(['BON', 'MOYEN', 'MAUVAIS', 'DANGEREUX'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ecole_id.exists' => 'L\'école sélectionnée n\'existe pas.',
            'type.in' => 'Le type doit être ACADEMIQUE, ADMINISTRATIF, SPORTIF ou AUTRE.',
            'annee_construction.min' => 'L\'année de construction doit être supérieure à 1800.',
            'annee_construction.max' => 'L\'année de construction ne peut pas être dans le futur.',
            'superficie.min' => 'La superficie doit être positive.',
            'nombre_etages.min' => 'Le nombre d\'étages doit être au moins 1.',
            'etat.in' => 'L\'état doit être BON, MOYEN, MAUVAIS ou DANGEREUX.',
        ];
    }
}
