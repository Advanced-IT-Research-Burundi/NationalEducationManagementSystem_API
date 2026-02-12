<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCentreExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ecole_id' => 'required|exists:ecoles,id',
            'session_id' => 'required|exists:sessions_examen,id',
            'capacite' => 'required|integer|min:1',
            'responsable_id' => 'required|exists:users,id',
        ];
    }
}
