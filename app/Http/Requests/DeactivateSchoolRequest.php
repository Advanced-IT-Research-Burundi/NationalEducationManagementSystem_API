<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;

class DeactivateSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');
        // Only users with delete or manage_schools permission can deactivate
        return $this->user()->can('delete', $school) 
            || $this->user()->hasPermission('manage_schools');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Validate that the school can be deactivated
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $school = $this->route('school');
            
            if ($school->statut !== School::STATUS_ACTIVE) {
                $validator->errors()->add('statut', 'Seules les écoles actives peuvent être désactivées.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Une raison est requise pour désactiver l\'école.',
            'reason.max' => 'La raison ne peut pas dépasser 500 caractères.',
        ];
    }
}
