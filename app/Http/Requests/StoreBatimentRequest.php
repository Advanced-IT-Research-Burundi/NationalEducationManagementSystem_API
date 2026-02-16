<?php

namespace App\Http\Requests;

use App\Models\Batiment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatimentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Batiment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ecole_id' => ['required', 'exists:ecoles,id'],
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['ACADEMIQUE', 'ADMINISTRATIF', 'SPORTIF', 'AUTRE'])],
            'annee_construction' => ['nullable', 'integer', 'min:1800', 'max:'.(date('Y') + 5)],
            'superficie' => ['nullable', 'numeric', 'min:0'],
            'nombre_etages' => ['required', 'integer', 'min:1', 'max:50'],
            'etat' => ['required', Rule::in(['BON', 'MOYEN', 'MAUVAIS', 'DANGEREUX'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ecole_id.required' => 'L\'école est requise.',
            'ecole_id.exists' => 'L\'école sélectionnée n\'existe pas.',
            'nom.required' => 'Le nom du bâtiment est requis.',
            'type.required' => 'Le type de bâtiment est requis.',
            'type.in' => 'Le type doit être ACADEMIQUE, ADMINISTRATIF, SPORTIF ou AUTRE.',
            'annee_construction.min' => 'L\'année de construction doit être supérieure à 1800.',
            'annee_construction.max' => 'L\'année de construction ne peut pas être dans le futur.',
            'superficie.min' => 'La superficie doit être positive.',
            'nombre_etages.required' => 'Le nombre d\'étages est requis.',
            'nombre_etages.min' => 'Le nombre d\'étages doit être au moins 1.',
            'etat.required' => 'L\'état du bâtiment est requis.',
            'etat.in' => 'L\'état doit être BON, MOYEN, MAUVAIS ou DANGEREUX.',
        ];
    }
}
