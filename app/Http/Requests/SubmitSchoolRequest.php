<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;

class SubmitSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');
        return $this->user()->can('update', $school);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Optional fields for updating before submission
            'name' => ['sometimes', 'string', 'max:255'],
            'code_ecole' => ['sometimes', 'string', 'max:50'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Validate that the school can be submitted
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $school = $this->route('school');
            
            if ($school->statut !== School::STATUS_BROUILLON) {
                $validator->errors()->add('statut', 'Seules les écoles en brouillon peuvent être soumises.');
            }

            if (!$school->hasRequiredFieldsForSubmission()) {
                $validator->errors()->add('fields', 'Tous les champs obligatoires doivent être remplis avant la soumission (nom, code, type, niveau, colline).');
            }
        });
    }
}
