<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscription_examen_id' => 'required|exists:inscriptions_examen,id|unique:certificats,inscription_examen_id',
            'numero_unique' => 'required|string|unique:certificats,numero_unique',
            'date_emission' => 'required|date',
            'qr_code' => 'sometimes|string',
        ];
    }
}
