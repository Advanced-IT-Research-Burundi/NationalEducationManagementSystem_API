<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInscriptionExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eleve_id' => 'sometimes|exists:eleves,id',
            'session_id' => 'sometimes|exists:sessions_examen,id',
            'centre_id' => 'sometimes|exists:centres_examen,id',
            'numero_anonymat' => 'sometimes|string|unique:inscriptions_examen,numero_anonymat,' . $this->route('inscription_examen'),
            'statut' => 'sometimes|in:inscrit,present,absent,exclu',
        ];
    }
}
