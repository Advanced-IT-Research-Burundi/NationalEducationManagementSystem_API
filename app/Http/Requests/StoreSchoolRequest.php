<?php

namespace App\Http\Requests;

use App\Models\Colline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\School::class);
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
            'code_ecole' => ['nullable', 'string', 'max:50', 'unique:schools,code_ecole'],
            'type_ecole' => ['required', Rule::in(['PUBLIQUE', 'PRIVEE', 'ECC', 'AUTRE'])],
            'niveau' => ['required', Rule::in(['FONDAMENTAL', 'POST_FONDAMENTAL', 'SECONDAIRE', 'SUPERIEUR'])],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'colline_id' => ['required', 'exists:collines,id'], // Key for auto-localization
        ];
    }

    /**
     * Prepare the data for validation.
     * We don't auto-fill here for validation mainly, but we could.
     * The auto-filling happens better in the Controller or an Observer.
     * But verification of colline is key.
     */
}
