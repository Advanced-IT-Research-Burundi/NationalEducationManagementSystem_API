<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NoteController extends Controller
{
    /**
     * Consultation des notes avec filtres en cascade.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Note::with([
            'eleve:id,nom,prenom,matricule',
            'evaluation' => function ($q) {
                $q->select('id', 'classe_id', 'cours_id', 'trimestre', 'type_evaluation', 'note_maximale', 'annee_scolaire_id', 'date_passation');
            },
            'evaluation.classe:id,nom,code',
            'evaluation.cours:id,nom,code',
            'evaluation.anneeScolaire:id,libelle,code',
        ]);

        // Filter by classe (through evaluation)
        if ($request->filled('classe_id')) {
            $query->whereHas('evaluation', function ($q) use ($request) {
                $q->where('classe_id', $request->integer('classe_id'));
            });
        }

        if ($request->filled('school_id')) {
            $query->whereHas('evaluation.classe', function ($q) use ($request) {
                $q->where('school_id', $request->integer('school_id'));
            });
        } elseif (auth()->check() && auth()->user()->school_id) {
            $query->whereHas('evaluation.classe', function ($q) {
                $q->where('school_id', auth()->user()->school_id);
            });
        }

        // Filter by cours (through evaluation)
        if ($request->filled('cours_id')) {
            $query->whereHas('evaluation', function ($q) use ($request) {
                $q->where('cours_id', $request->integer('cours_id'));
            });
        }

        // Filter by trimestre
        if ($request->filled('trimestre')) {
            $query->whereHas('evaluation', function ($q) use ($request) {
                $q->where('trimestre', $request->trimestre);
            });
        }

        // Filter by annee_scolaire
        if ($request->filled('annee_scolaire_id')) {
            $query->whereHas('evaluation', function ($q) use ($request) {
                $q->where('annee_scolaire_id', $request->integer('annee_scolaire_id'));
            });
        }

        // Filter by section (through evaluation.cours)
        if ($request->filled('section_id')) {
            if (Schema::hasColumn('matieres', 'section_id')) {
                $query->whereHas('evaluation.cours', function ($q) use ($request) {
                    $q->where('section_id', $request->integer('section_id'));
                });
            }
        }

        // Filter by eleve
        if ($request->filled('eleve_id')) {
            $query->where('eleve_id', $request->integer('eleve_id'));
        }

        $notes = $query->latest()->paginate($request->get('per_page', 50));

        return response()->json($notes);
    }
}
