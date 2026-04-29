<?php

namespace App\Http\Requests;

use App\Models\Colline;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', School::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
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
            'geo_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'directeur_name' => ['nullable', 'string', 'max:255'],
            'capacite_accueil' => ['nullable', 'integer', 'min:0'],
            'adresse_physique' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'annee_creation' => ['nullable', 'string', 'max:4'],
            'colline_id' => ['required', 'exists:collines,id'], // Key for auto-localization
            'niveau_scolaire_ids' => ['nullable', 'array'],
            'niveau_scolaire_ids.*' => ['exists:niveaux_scolaires,id'],
        ];
    }

    /**
     * Prepare the data for validation.
     * We don't auto-fill here for validation mainly, but we could.
     * The auto-filling happens better in the Controller or an Observer.
     * But verification of colline is key.
     */
}
