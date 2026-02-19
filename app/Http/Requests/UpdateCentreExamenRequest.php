<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCentreExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => 'sometimes|exists:schools,id',
            'session_id' => 'sometimes|exists:sessions_examen,id',
            'capacite' => 'sometimes|integer|min:1',
            'responsable_id' => 'sometimes|exists:users,id',
        ];
    }
}
