<?php

namespace App\Http\Requests;

use App\Enums\TypeMouvement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMouvementEleveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type_mouvement' => ['sometimes', Rule::enum(TypeMouvement::class)],
            'date_mouvement' => ['sometimes', 'date', 'before_or_equal:today'],
            'ecole_destination_id' => [
                'nullable',
                'exists:ecoles,id',
                'different:ecole_origine_id',
            ],
            'motif' => ['sometimes', 'string', 'min:10', 'max:1000'],
            'document_reference' => ['nullable', 'string', 'max:255'],
            'document_path' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type_mouvement.Illuminate\Validation\Rules\Enum' => 'Le type de mouvement n\'est pas valide.',
            'date_mouvement.date' => 'La date du mouvement n\'est pas valide.',
            'date_mouvement.before_or_equal' => 'La date du mouvement ne peut pas être dans le futur.',
            'ecole_destination_id.different' => 'L\'école de destination doit être différente de l\'école d\'origine.',
            'motif.min' => 'Le motif doit contenir au moins :min caractères.',
            'motif.max' => 'Le motif ne peut pas dépasser :max caractères.',
        ];
    }
}
