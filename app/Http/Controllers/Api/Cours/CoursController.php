<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoursRequest;
use App\Http\Requests\UpdateCoursRequest;
use App\Models\Matiere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CoursController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $with = [];
        if (Schema::hasColumn('matieres', 'categorie_cours_id')) {
            $with[] = 'categorieCours:id,nom,ordre';
        }
        if (Schema::hasColumn('matieres', 'enseignant_id')) {
            $with[] = 'enseignant.user:id,name';
            $with[] = 'enseignant';
        }
        if (Schema::hasColumn('matieres', 'section_id')) {
            $with[] = 'section:id,nom,code';
            $with[] = 'section';
        }
        if (Schema::hasColumn('matieres', 'niveau_id')) {
            $with[] = 'niveau:id,nom,code';
            $with[] = 'niveaux:id,nom,code';
            $with[] = 'sections:id,nom,code';
            $with[] = 'niveau';
        }

        $query = Matiere::query()->with($with);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('categorie_cours_id')) {
            if (Schema::hasColumn('matieres', 'categorie_cours_id')) {
                $query->byCategorie($request->integer('categorie_cours_id'));
            }
        }

        if ($request->filled('section_id')) {
            if (Schema::hasColumn('matieres', 'section_id')) {
                $query->bySection($request->integer('section_id'));
            }
        }

        if ($request->filled('niveau_id')) {
            if (Schema::hasColumn('matieres', 'niveau_id')) {
                $query->where('niveau_id', $request->integer('niveau_id'));
            }
        }

        

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        if ($request->filled('school_id')) {
            $query->forSchool($request->integer('school_id'));
        } elseif (auth()->check() && auth()->user()->school_id) {
            $query->forSchool(auth()->user()->school_id);
        }

        if ($request->filled('est_principale')) {
            if (Schema::hasColumn('matieres', 'est_principale')) {
                $query->where('est_principale', $request->boolean('est_principale'));
            }
        }

        $cours = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($cours);
    }

    public function list(Request $request): JsonResponse
    {
        $query = Matiere::active()->orderBy('nom');

        if ($request->filled('niveau_id')) {
            if (Schema::hasColumn('matieres', 'niveau_id')) {
                $query->byNiveau($request->integer('niveau_id'));
            }
        }

        if ($request->filled('section_id')) {
            if (Schema::hasColumn('matieres', 'section_id')) {
                $query->bySection($request->integer('section_id'));
            }
        }

        if ($request->filled('categorie_cours_id')) {
            if (Schema::hasColumn('matieres', 'categorie_cours_id')) {
                $query->byCategorie($request->integer('categorie_cours_id'));
            }
        }

        if ($request->filled('school_id')) {
            $query->forSchool($request->integer('school_id'));
        } elseif (auth()->check() && auth()->user()->school_id) {
            $query->forSchool(auth()->user()->school_id);
        }

        if (auth()->check() && auth()->user()->hasRole('Enseignant')) {
            $enseignantId = auth()->user()->enseignant->id ?? null;
            if ($enseignantId) {
                $query->whereHas('enseignants', function ($q) use ($enseignantId) {
                    $q->where('enseignants.id', $enseignantId);
                });
            }
        }

        $columns = ['id', 'nom', 'code'];
        foreach (['niveau_id', 'section_id', 'categorie_cours_id', 'ponderation_tj', 'ponderation_examen'] as $col) {
            if (Schema::hasColumn('matieres', $col)) {
                $columns[] = $col;
            }
        }
        $cours = $query->with(['niveaux:id', 'sections:id'])->get($columns);

        return response()->json($cours);
    }

    public function store(StoreCoursRequest $request): JsonResponse
    {
        $data = $request->validated();
        $cours = Matiere::create($data);

        if (isset($data['niveau_ids'])) {
            $cours->niveaux()->sync($data['niveau_ids']);
        } elseif (isset($data['niveau_id']) && $data['niveau_id'] !== "_none") {
            $cours->niveaux()->sync([$data['niveau_id']]);
        }

        if (isset($data['section_ids'])) {
            $cours->sections()->sync($data['section_ids']);
        } elseif (isset($data['section_id']) && $data['section_id'] !== "_none") {
            $cours->sections()->sync([$data['section_id']]);
        }

        return response()->json([
            'message' => 'Cours créé avec succès',
            'data' => $cours->load(['categorieCours', 'enseignant.user', 'sections', 'niveaux']),
        ], 201);
    }

    public function show(Matiere $cour): JsonResponse
    {
        return response()->json([
            'data' => $cour->load([
                'categorieCours',
                'enseignant.user',
                'sections',
                'niveaux',
            ]),
        ]);
    }

    public function update(UpdateCoursRequest $request, Matiere $cour): JsonResponse
    {
        $data = $request->validated();
        $cour->update($data);

        if (isset($data['niveau_ids'])) {
            $cour->niveaux()->sync($data['niveau_ids']);
        } elseif (isset($data['niveau_id']) && $data['niveau_id'] !== "_none") {
            $cour->niveaux()->sync([$data['niveau_id']]);
        }

        if (isset($data['section_ids'])) {
            $cour->sections()->sync($data['section_ids']);
        } elseif (isset($data['section_id']) && $data['section_id'] !== "_none") {
            $cour->sections()->sync([$data['section_id']]);
        }

        return response()->json([
            'message' => 'Cours mis à jour avec succès',
            'data' => $cour->load(['categorieCours', 'enseignant.user', 'sections', 'niveaux']),
        ]);
    }

    public function destroy(Matiere $cour): JsonResponse
    {
        if ($cour->evaluations()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce cours car il a des évaluations associées.',
            ], 422);
        }

        $cour->delete();

        return response()->json(['message' => 'Cours supprimé avec succès']);
    }
}
