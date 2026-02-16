<?php

namespace App\Http\Requests;

use App\Models\Maintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Maintenance::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'maintenable_type' => ['required', Rule::in(['App\\Models\\Batiment', 'App\\Models\\Equipement'])],
            'maintenable_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', Rule::in(['PREVENTIVE', 'CORRECTIVE', 'URGENCE'])],
            'description' => ['required', 'string'],
            'date_demande' => ['required', 'date'],
            'date_intervention' => ['nullable', 'date', 'after_or_equal:date_demande'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_intervention'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'statut' => ['required', Rule::in(['DEMANDE', 'EN_COURS', 'TERMINE', 'ANNULE'])],
            'rapport' => ['nullable', 'string'],
            'technicien_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'maintenable_type.required' => 'Le type d\'élément est requis.',
            'maintenable_type.in' => 'Le type doit être un bâtiment ou un équipement.',
            'maintenable_id.required' => 'L\'identifiant de l\'élément est requis.',
            'type.required' => 'Le type de maintenance est requis.',
            'type.in' => 'Le type doit être PREVENTIVE, CORRECTIVE ou URGENCE.',
            'description.required' => 'La description est requise.',
            'date_demande.required' => 'La date de demande est requise.',
            'date_intervention.after_or_equal' => 'La date d\'intervention doit être postérieure ou égale à la date de demande.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date d\'intervention.',
            'cout.min' => 'Le coût doit être positif.',
            'statut.required' => 'Le statut est requis.',
            'statut.in' => 'Le statut doit être DEMANDE, EN_COURS, TERMINE ou ANNULE.',
            'technicien_id.exists' => 'Le technicien sélectionné n\'existe pas.',
        ];
    }
}
