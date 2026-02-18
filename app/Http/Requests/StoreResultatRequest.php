<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResultatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscription_examen_id' => 'required|exists:inscriptions_examen,id',
            'matiere' => 'required|string',
            'note' => 'required|numeric|min:0|max:100',
            'mention' => 'sometimes|string',
            'deliberation' => 'sometimes|string',
        ];
    }
}
