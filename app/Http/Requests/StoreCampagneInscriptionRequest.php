<?php

namespace App\Http\Requests;

use App\Enums\CampagneType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampagneInscriptionRequest extends FormRequest
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
        return [
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],
            'school_id' => ['required', 'exists:ecoles,id'],
            'type' => ['required', Rule::in(CampagneType::values())],
            'date_ouverture' => ['required', 'date'],
            'date_cloture' => ['required', 'date', 'after:date_ouverture'],
            'quota_max' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'annee_scolaire_id.required' => 'L\'année scolaire est requise.',
            'annee_scolaire_id.exists' => 'L\'année scolaire sélectionnée n\'existe pas.',
            'school_id.required' => 'L\'école est requise.',
            'school_id.exists' => 'L\'école sélectionnée n\'existe pas.',
            'type.required' => 'Le type de campagne est requis.',
            'type.in' => 'Le type de campagne doit être "nouvelle" ou "reinscription".',
            'date_ouverture.required' => 'La date d\'ouverture est requise.',
            'date_ouverture.date' => 'La date d\'ouverture doit être une date valide.',
            'date_cloture.required' => 'La date de clôture est requise.',
            'date_cloture.date' => 'La date de clôture doit être une date valide.',
            'date_cloture.after' => 'La date de clôture doit être postérieure à la date d\'ouverture.',
            'quota_max.integer' => 'Le quota doit être un nombre entier.',
            'quota_max.min' => 'Le quota doit être au moins 1.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if campagne already exists for this année/école/type
            if (! $validator->errors()->any()) {
                $exists = \App\Models\CampagneInscription::where('annee_scolaire_id', $this->annee_scolaire_id)
                    ->where('school_id', $this->school_id)
                    ->where('type', $this->type)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'type',
                        'Une campagne de ce type existe déjà pour cette école et cette année scolaire.'
                    );
                }
            }
        });
    }
}
