<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
}
