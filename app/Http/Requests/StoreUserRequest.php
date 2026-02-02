<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $data = $this->all();

            // Ensure school_id is required when admin_level is ECOLE
            if (isset($data['admin_level']) && $data['admin_level'] === 'ECOLE') {
                if (empty($data['school_id'])) {
                    $validator->errors()->add('school_id', 'Le school_id est requis pour les utilisateurs de niveau ECOLE.');
                }
            }

            // Hierarchical Validation Logic (Prevent privilege escalation or cross-domain creation)
            if ($user->hasRole('Admin National')) {
                return; // Can do anything
            }

            // Provincial Admin can only create users in their province
            if ($user->admin_level === 'PROVINCE') {
                if (isset($data['province_id']) && $data['province_id'] != $user->admin_entity_id) {
                    $validator->errors()->add('province_id', 'Vous ne pouvez pas créer des utilisateurs pour une autre province.');
                }
            }

            // Communal Officer can only create users in their commune
            if ($user->admin_level === 'COMMUNE') {
                if (isset($data['commune_id']) && $data['commune_id'] != $user->admin_entity_id) {
                    $validator->errors()->add('commune_id', 'Vous ne pouvez pas créer des utilisateurs pour une autre commune.');
                }
            }

            // Zone Supervisor can only create users in their zone
            if ($user->admin_level === 'ZONE') {
                if (isset($data['zone_id']) && $data['zone_id'] != $user->admin_entity_id) {
                    $validator->errors()->add('zone_id', 'Vous ne pouvez pas créer des utilisateurs pour une autre zone.');
                }
            }

            // School-level users can only create users in their own school
            if ($user->admin_level === 'ECOLE') {
                if (isset($data['school_id']) && $data['school_id'] != $user->admin_entity_id) {
                    $validator->errors()->add('school_id', 'Vous ne pouvez pas créer des utilisateurs pour une autre école.');
                }
                // School users cannot create higher-level users
                if (isset($data['admin_level']) && $data['admin_level'] !== 'ECOLE') {
                    $validator->errors()->add('admin_level', 'Vous ne pouvez créer que des utilisateurs de niveau ECOLE.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     * Sync admin_entity_id with school_id when admin_level is ECOLE.
     */
    protected function prepareForValidation(): void
    {
        if ($this->admin_level === 'ECOLE' && $this->school_id) {
            $this->merge([
                'admin_entity_id' => $this->school_id,
            ]);
        }
    }
}
