<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InscriptionUpdateRequest extends FormRequest
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
            /* INSCRIPTION */
            'inscription.eleve_id' => ['sometimes', 'exists:eleves,id'],
            'inscription.classe_id' => ['sometimes', 'exists:classes,id'],
            'inscription.school_id' => ['sometimes', 'exists:schools,id'],
            'inscription.annee_scolaire_id' => ['sometimes', 'exists:annee_scolaires,id'],
            'inscription.niveau_demande_id' => ['sometimes', 'exists:niveaux_scolaires,id'],
            'inscription.date_inscription' => ['sometimes', 'date'],
            'inscription.type_inscription' => [
                'sometimes',
                'in:nouvelle,reinscription,transfert_entrant'
            ],
            'inscription.est_redoublant' => ['sometimes', 'boolean'],
            'inscription.pieces_fournies' => ['sometimes', 'array'],
            'inscription.pieces_fournies.*' => ['string'],
            'inscription.observations' => ['nullable', 'string'],

            /* AFFECTATION */
            'affectation.classe_id' => ['sometimes', 'exists:classes,id'],
            'affectation.date_affectation' => ['sometimes', 'date'],
            'affectation.numero_ordre' => ['nullable', 'integer', 'min:1'],

            /* MOUVEMENT */
            'mouvement.type_mouvement' => [
                'sometimes',
                'in:transfert_sortant,transfert_entrant,abandon,exclusion,deces,passage,redoublement,reintegration'
            ],
            'mouvement.date_mouvement' => ['sometimes', 'date'],
            'mouvement.ecole_origine_id' => ['nullable', 'exists:schools,id'],
            'mouvement.classe_origine_id' => ['nullable', 'exists:classes,id'],
            'mouvement.motif' => ['sometimes', 'string', 'min:5'],
            'mouvement.document_reference' => ['nullable', 'string'],
        ];
    }
}
