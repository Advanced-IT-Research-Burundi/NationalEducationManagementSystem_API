<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InscriptionController extends Controller
{
    public function index()
    {
        //
    }
    public function store(Request $request)
    {
        {
  "inscription": {
    "eleve_id": 12,
    "classe_id": 5,
    "ecole_id": 2,
    "annee_scolaire_id": 3,
    "niveau_demande_id": 4,
    "date_inscription": "2025-09-01",
    "type_inscription": "nouvelle",
    "est_redoublant": false,
    "pieces_fournies": [
      "acte_naissance",
      "bulletin_precedent",
      "certificat_medical"
    ],
    "observations": "Inscription normale",
    "created_by": 1
  },

  "affectation": {
    "classe_id": 5,
    "date_affectation": "2025-09-01",
    "numero_ordre": 18,
    "created_by": 1
  },

  "mouvement": {
    "type_mouvement": "transfert_entrant",
    "date_mouvement": "2025-09-01",
    "ecole_origine_id": 1,
    "classe_origine_id": 8,
    "motif": "Changement d'établissement",
    "document_reference": "DECISION-2025-014",
    "created_by": 1
  }
}

    }
}
