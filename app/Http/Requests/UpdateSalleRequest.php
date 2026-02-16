<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('salle'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'batiment_id' => ['sometimes', 'exists:batiments,id'],
            'numero' => ['sometimes', 'string', 'max:50'],
            'type' => ['sometimes', Rule::in(['CLASSE', 'LABORATOIRE', 'BUREAU', 'SANITAIRE', 'BIBLIOTHEQUE', 'AUTRE'])],
            'capacite' => ['nullable', 'integer', 'min:1'],
            'superficie' => ['nullable', 'numeric', 'min:0'],
            'etat' => ['sometimes', Rule::in(['BON', 'MOYEN', 'MAUVAIS', 'DANGEREUX'])],
            'accessible_handicap' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'batiment_id.exists' => 'Le bâtiment sélectionné n\'existe pas.',
            'type.in' => 'Le type doit être CLASSE, LABORATOIRE, BUREAU, SANITAIRE, BIBLIOTHEQUE ou AUTRE.',
            'capacite.min' => 'La capacité doit être au moins 1.',
            'superficie.min' => 'La superficie doit être positive.',
            'etat.in' => 'L\'état doit être BON, MOYEN, MAUVAIS ou DANGEREUX.',
        ];
    }
}
