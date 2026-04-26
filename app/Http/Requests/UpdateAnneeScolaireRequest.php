<?php

namespace App\Http\Requests;

use App\Models\AnneeScolaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnneeScolaireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage_schools');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Récupère l'ID à ignorer quel que soit le nom du paramètre utilisé par la route
        // (`anneeScolaire`, `annee_scolaire`, `annees_scolaire`...) — on cherche dans
        // l'ordre une instance de modèle, puis la dernière valeur scalaire de l'URL.
        $ignoreId = null;
        $route = $this->route();
        if ($route) {
            foreach ($route->parameters() as $param) {
                if ($param instanceof AnneeScolaire) {
                    $ignoreId = $param->id;
                    break;
                }
            }
            if (!$ignoreId) {
                foreach ($route->parameters() as $param) {
                    if (is_numeric($param)) {
                        $ignoreId = (int) $param;
                        break;
                    }
                }
            }
        }

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('annee_scolaires', 'code')->ignore($ignoreId),
            ],
            'libelle' => ['sometimes', 'required', 'string', 'max:100'],
            'date_debut' => ['sometimes', 'required', 'date'],
            'date_fin' => ['sometimes', 'required', 'date', 'after:date_debut'],
            'est_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Le code de l\'année scolaire est requis.',
            'code.unique' => 'Ce code d\'année scolaire existe déjà.',
            'code.max' => 'Le code ne peut pas dépasser 20 caractères.',
            'libelle.required' => 'Le libellé est requis.',
            'libelle.max' => 'Le libellé ne peut pas dépasser 100 caractères.',
            'date_debut.required' => 'La date de début est requise.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.required' => 'La date de fin est requise.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}
