<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InscriptionStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /* =======================
             |  INSCRIPTION
             =======================*/
            'inscription.eleve_id' => ['required', 'exists:eleves,id'],
            'inscription.classe_id' => ['required', 'exists:classes,id'],
            'inscription.school_id' => ['required', 'exists:schools,id'],
            'inscription.annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],
            'inscription.niveau_demande_id' => ['required', 'exists:niveaux_scolaires,id'],
            'inscription.date_inscription' => ['required', 'date'],
            'inscription.type_inscription' => [
                'required',
                'in:nouvelle,reinscription,transfert_entrant'
            ],
            'inscription.est_redoublant' => ['required', 'boolean'],
            'inscription.pieces_fournies' => ['nullable', 'array'],
            'inscription.pieces_fournies.*' => ['string'],
            'inscription.observations' => ['nullable', 'string'],
            'inscription.created_by' => ['required', 'exists:users,id'],

            /* =======================
             |  AFFECTATION CLASSE
             =======================*/
            'affectation.classe_id' => ['required', 'exists:classes,id'],
            'affectation.date_affectation' => ['required', 'date'],
            'affectation.numero_ordre' => ['nullable', 'integer', 'min:1'],
            'affectation.created_by' => ['required', 'exists:users,id'],

            /* =======================
             |  MOUVEMENT ELEVE
             =======================*/
            'mouvement.type_mouvement' => [
                'required',
                'in:transfert_sortant,transfert_entrant,abandon,exclusion,deces,passage,redoublement,reintegration'
            ],
            'mouvement.date_mouvement' => ['required', 'date'],
            'mouvement.ecole_origine_id' => ['nullable', 'exists:ecoles,id'],
            'mouvement.classe_origine_id' => ['nullable', 'exists:classes,id'],
            'mouvement.motif' => ['required', 'string', 'min:5'],
            'mouvement.document_reference' => ['nullable', 'string'],
            'mouvement.created_by' => ['required', 'exists:users,id'],
        ];
    }
}
