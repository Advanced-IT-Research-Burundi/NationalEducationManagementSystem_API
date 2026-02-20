<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscription_examen_id' => 'sometimes|exists:inscriptions_examen,id|unique:certificats,inscription_examen_id,' . $this->route('certificate'),
            'numero_unique' => 'sometimes|string|unique:certificats,numero_unique,' . $this->route('certificate'),
            'date_emission' => 'sometimes|date',
            'qr_code' => 'sometimes|string',
        ];
    }
}
