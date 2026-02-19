<?php

namespace App\Http\Requests;

use App\Enums\TypeMouvement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMouvementEleveRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'exists:eleves,id'],
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],
            'type_mouvement' => ['required', Rule::enum(TypeMouvement::class)],
            'date_mouvement' => ['required', 'date', 'before_or_equal:today'],
            'ecole_origine_id' => ['nullable', 'exists:schools,id'],
            'ecole_destination_id' => [
                'nullable',
                'exists:schools,id',
                'different:ecole_origine_id',
            ],
            'classe_origine_id' => ['nullable', 'exists:classes,id'],
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
            'document_reference' => ['nullable', 'string', 'max:255'],
            'document_path' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'eleve_id.required' => 'L\'élève est obligatoire.',
            'eleve_id.exists' => 'L\'élève sélectionné n\'existe pas.',
            'annee_scolaire_id.required' => 'L\'année scolaire est obligatoire.',
            'annee_scolaire_id.exists' => 'L\'année scolaire sélectionnée n\'existe pas.',
            'type_mouvement.required' => 'Le type de mouvement est obligatoire.',
            'type_mouvement.Illuminate\Validation\Rules\Enum' => 'Le type de mouvement n\'est pas valide.',
            'date_mouvement.required' => 'La date du mouvement est obligatoire.',
            'date_mouvement.date' => 'La date du mouvement n\'est pas valide.',
            'date_mouvement.before_or_equal' => 'La date du mouvement ne peut pas être dans le futur.',
            'ecole_destination_id.different' => 'L\'école de destination doit être différente de l\'école d\'origine.',
            'motif.required' => 'Le motif est obligatoire.',
            'motif.min' => 'Le motif doit contenir au moins :min caractères.',
            'motif.max' => 'Le motif ne peut pas dépasser :max caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'eleve_id' => 'élève',
            'annee_scolaire_id' => 'année scolaire',
            'type_mouvement' => 'type de mouvement',
            'date_mouvement' => 'date du mouvement',
            'ecole_origine_id' => 'école d\'origine',
            'ecole_destination_id' => 'école de destination',
            'classe_origine_id' => 'classe d\'origine',
            'motif' => 'motif',
            'document_reference' => 'référence du document',
            'observations' => 'observations',
        ];
    }
}
