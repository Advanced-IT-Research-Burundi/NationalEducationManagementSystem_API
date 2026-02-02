<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNiveauRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('niveaux', 'code')->ignore($this->route('niveau'))],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'cycle' => ['sometimes', Rule::in(['PRIMAIRE', 'FONDAMENTAL', 'POST_FONDAMENTAL', 'SECONDAIRE', 'SUPERIEUR'])],
            'description' => ['nullable', 'string', 'max:500'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
