<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResultatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscription_examen_id' => 'sometimes|exists:inscriptions_examen,id',
            'matiere' => 'sometimes|string',
            'note' => 'sometimes|numeric|min:0|max:100',
            'mention' => 'sometimes|string',
            'deliberation' => 'sometimes|string',
        ];
    }
}
