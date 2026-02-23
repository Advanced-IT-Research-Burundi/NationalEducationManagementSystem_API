<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $matiereId = $this->route('matiere')->id;

        return [
            'nom' => ['sometimes', 'required', 'string', 'max:100'],
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:matieres,code,' . $matiereId],
            'description' => ['nullable', 'string', 'max:500'],
            'coefficient' => ['nullable', 'integer', 'min:1', 'max:10'],
            'heures_par_semaine' => ['nullable', 'integer', 'min:0', 'max:20'],
            'actif' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la matière est requis.',
            'code.required' => 'Le code de la matière est requis.',
            'code.unique' => 'Ce code de matière existe déjà.',
        ];
    }
}
