<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatiereRequest;
use App\Http\Requests\UpdateMatiereRequest;
use App\Models\Matiere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatiereController extends Controller
{
    /**
     * Display a listing of matieres.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Matiere::query()->with([
            'niveau:id,nom,code,type_id',
            'niveaux:id,nom,code,type_id',
            'sections:id,nom,code',
            'niveau.typeScolaire:id,nom',
        ]);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        if ($request->filled('niveau_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('niveau_id', $request->niveau_id)
                  ->orWhereHas('niveaux', function ($nq) use ($request) {
                      $nq->where('niveaux_scolaires.id', $request->niveau_id);
                  });
            });
        }
        
        if ($request->filled('section_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('section_id', $request->section_id)
                  ->orWhereHas('sections', function ($sq) use ($request) {
                      $sq->where('sections.id', $request->section_id);
                  });
            });
        }
        
        if ($request->filled('section_ids')) {
            $query->whereHas('sections', function ($sq) use ($request) {
                $sq->whereIn('sections.id', (array) $request->section_ids);
            });
        }

        if ($request->filled('school_id')) {
            $query->forSchool($request->integer('school_id'));
        } elseif (auth()->check() && auth()->user()->school_id) {
            $query->forSchool(auth()->user()->school_id);
        }

        $matieres = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($matieres);
    }

    /**
     * Lightweight list for dropdowns.
     */
    public function list(Request $request): JsonResponse
    {
        $query = Matiere::active()->with(['niveaux:id', 'sections:id'])->orderBy('nom');

        if ($request->filled('niveau_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('niveau_id', $request->niveau_id)
                  ->orWhereHas('niveaux', function ($nq) use ($request) {
                      $nq->where('niveaux_scolaires.id', $request->niveau_id);
                  });
            });
        }
        
        if ($request->filled('section_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('section_id', $request->section_id)
                  ->orWhereHas('sections', function ($sq) use ($request) {
                      $sq->where('sections.id', $request->section_id);
                  });
            });
        }

        if ($request->filled('school_id')) {
            $query->forSchool($request->integer('school_id'));
        } elseif (auth()->check() && auth()->user()->school_id) {
            $query->forSchool(auth()->user()->school_id);
        }

        $matieres = $query->get(['id', 'nom', 'code', 'niveau_id', 'section_id']);

        return response()->json($matieres);
    }

    /**
     * Store a newly created matiere.
     */
    public function store(StoreMatiereRequest $request): JsonResponse
    {
        $data = $request->validated();
        $matiere = Matiere::create($data);

        if (isset($data['niveau_ids'])) {
            $matiere->niveaux()->sync($data['niveau_ids']);
        } elseif (isset($data['niveau_id'])) {
            $matiere->niveaux()->sync([$data['niveau_id']]);
        }

        if (isset($data['section_ids'])) {
            $matiere->sections()->sync($data['section_ids']);
        } elseif (isset($data['section_id'])) {
            $matiere->sections()->sync([$data['section_id']]);
        }

        return response()->json([
            'message' => 'Matière créée avec succès',
            'matiere' => $matiere->load(['niveaux', 'sections']),
        ], 201);
    }

    /**
     * Display the specified matiere.
     */
    public function show(Matiere $matiere): JsonResponse
    {
        return response()->json($matiere->load([
            'affectations.enseignant.user',
            'niveau:id,nom,code,type_id',
            'niveaux:id,nom,code,type_id',
            'sections:id,nom,code',
            'niveau.typeScolaire:id,nom',
        ]));
    }

    /**
     * Update the specified matiere.
     */
    public function update(UpdateMatiereRequest $request, Matiere $matiere): JsonResponse
    {
        $data = $request->validated();
        $matiere->update($data);

        if (isset($data['niveau_ids'])) {
            $matiere->niveaux()->sync($data['niveau_ids']);
        } elseif (isset($data['niveau_id'])) {
            $matiere->niveaux()->sync([$data['niveau_id']]);
        }

        if (isset($data['section_ids'])) {
            $matiere->sections()->sync($data['section_ids']);
        } elseif (isset($data['section_id'])) {
            $matiere->sections()->sync([$data['section_id']]);
        }

        return response()->json([
            'message' => 'Matière mise à jour avec succès',
            'matiere' => $matiere->load(['niveaux', 'sections']),
        ]);
    }

    /**
     * Remove the specified matiere.
     */
    public function destroy(Matiere $matiere): JsonResponse
    {
        if ($matiere->affectations()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette matière car elle est affectée à des enseignants.',
            ], 422);
        }

        $matiere->delete();

        return response()->json(['message' => 'Matière supprimée avec succès']);
    }
}
