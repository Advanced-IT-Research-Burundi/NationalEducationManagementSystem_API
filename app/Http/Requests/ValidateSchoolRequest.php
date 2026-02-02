<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;

class ValidateSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');
        return $this->user()->can('validate', $school);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Validate that the school can be validated
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $school = $this->route('school');
            
            if ($school->statut !== School::STATUS_EN_ATTENTE_VALIDATION) {
                $validator->errors()->add('statut', 'Seules les écoles en attente de validation peuvent être validées.');
            }

            if (!$school->hasRequiredFieldsForValidation()) {
                $validator->errors()->add('fields', 'L\'école doit avoir tous les champs requis remplis, y compris les coordonnées GPS (latitude, longitude).');
            }

            // Additional validation: ensure coordinates are valid
            if (is_null($school->latitude) || is_null($school->longitude)) {
                $validator->errors()->add('geolocation', 'La géolocalisation (latitude et longitude) est obligatoire pour activer une école.');
            }
        });
    }
}
