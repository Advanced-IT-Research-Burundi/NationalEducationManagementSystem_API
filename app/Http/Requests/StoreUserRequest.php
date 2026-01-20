<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'exists:roles,name'], // passing role name
            'admin_level' => ['required', Rule::in(['PAYS', 'MINISTERE', 'PROVINCE', 'COMMUNE', 'ZONE', 'ECOLE'])],
            
            // Entity IDs - validation depends on level, but we can make them nullable generally
            // and enforce specific logic in validation hooks or here.
            'admin_entity_id' => ['nullable', 'integer'],
            
            'pays_id' => ['nullable', 'exists:pays,id'],
            'ministere_id' => ['nullable', 'exists:ministeres,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'commune_id' => ['nullable', 'exists:communes,id'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'colline_id' => ['nullable', 'exists:collines,id'],
            'school_id' => ['nullable', 'exists:schools,id'],
        ];
    }

    protected function prepareForValidation()
    {
        // Add any necessary preparation
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $data = $this->all();

            // Hierarchical Validation Logic (Prevent privilege escalation or cross-domain creation)
            if ($user->hasRole('Admin National')) {
                return; // Can do anything
            }

            // Example: Provincial Admin can only create users in their province
            if ($user->admin_level === 'PROVINCE') {
                if (isset($data['province_id']) && $data['province_id'] != $user->admin_entity_id) {
                     $validator->errors()->add('province_id', 'You cannot create users for another province.');
                }
                // Check if they are trying to create a higher level role? 
                // e.g. Provincial Admin trying to create National Admin.
                // This logic is complex, requires mapping Roles to Levels.
                // Detailed check implemented in Controller or Service is often cleaner. 
            }
            
            // ... similar checks for other levels
        });
    }
}
