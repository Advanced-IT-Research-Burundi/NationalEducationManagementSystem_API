<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Using the policy 'update' method, passing the user model being updated.
        $user = $this->route('user'); // Assuming route param is 'user'

        return $this->user()->can('update', $user);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'], // Optional on update
            'role' => ['sometimes', 'string', 'exists:roles,name'],
            'admin_level' => ['sometimes', Rule::in(['PAYS', 'MINISTERE', 'PROVINCE', 'COMMUNE', 'ZONE', 'ECOLE'])],
            'admin_entity_id' => ['nullable', 'integer'],

            'pays_id' => ['nullable', 'exists:pays,id'],
            'ministere_id' => ['nullable', 'exists:ministeres,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'commune_id' => ['nullable', 'exists:communes,id'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'colline_id' => ['nullable', 'exists:collines,id'],
            'school_id' => ['nullable', 'exists:schools,id'],
            'statut' => ['sometimes', 'in:actif,inactif'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $data = $this->all();
            $targetUser = $this->route('user');

            // Ensure school_id is provided when changing admin_level to ECOLE
            if (isset($data['admin_level']) && $data['admin_level'] === 'ECOLE') {
                $schoolId = $data['school_id'] ?? $targetUser->school_id;
                if (empty($schoolId)) {
                    $validator->errors()->add('school_id', 'Le school_id est requis pour les utilisateurs de niveau ECOLE.');
                }
            }

            // Hierarchical Validation Logic
            if ($user->hasRole('Admin National')) {
                return; // Can do anything
            }

            // School-level users can only update users in their own school
            if ($user->admin_level === 'ECOLE') {
                if ($targetUser->school_id !== $user->admin_entity_id) {
                    $validator->errors()->add('school_id', 'Vous ne pouvez modifier que des utilisateurs de votre école.');
                }
                // School users cannot change users to a higher level
                if (isset($data['admin_level']) && $data['admin_level'] !== 'ECOLE') {
                    $validator->errors()->add('admin_level', 'Vous ne pouvez pas changer le niveau administratif.');
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
