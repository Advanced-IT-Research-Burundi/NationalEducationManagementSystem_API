<?php

namespace App\Http\Requests;

use App\Models\Salle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Salle::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'batiment_id' => ['nullable', 'exists:batiments,id'],
            'numero' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['CLASSE', 'LABORATOIRE', 'BUREAU', 'SANITAIRE', 'BIBLIOTHEQUE', 'AUTRE'])],
            'capacite' => ['nullable', 'integer', 'min:1'],
            'superficie' => ['nullable', 'numeric', 'min:0'],
            'etat' => ['required', Rule::in(['BON', 'MOYEN', 'MAUVAIS', 'DANGEREUX'])],
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
            'batiment_id.required' => 'Le bâtiment est requis.',
            'batiment_id.exists' => 'Le bâtiment sélectionné n\'existe pas.',
            'numero.required' => 'Le numéro de la salle est requis.',
            'type.required' => 'Le type de salle est requis.',
            'type.in' => 'Le type doit être CLASSE, LABORATOIRE, BUREAU, SANITAIRE, BIBLIOTHEQUE ou AUTRE.',
            'capacite.min' => 'La capacité doit être au moins 1.',
            'superficie.min' => 'La superficie doit être positive.',
            'etat.required' => 'L\'état de la salle est requis.',
            'etat.in' => 'L\'état doit être BON, MOYEN, MAUVAIS ou DANGEREUX.',
        ];
    }
}
