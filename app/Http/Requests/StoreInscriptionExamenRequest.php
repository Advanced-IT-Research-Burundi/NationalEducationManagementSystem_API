<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInscriptionExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eleve_id' => 'required|exists:eleves,id',
            'session_id' => 'required|exists:sessions_examen,id',
            'centre_id' => 'sometimes|exists:centres_examen,id',
            'numero_anonymat' => 'sometimes|string|unique:inscriptions_examen,numero_anonymat',
            'statut' => 'sometimes|in:inscrit,present,absent,exclu',
        ];
    }
}
