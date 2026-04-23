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
                $query->where('niveau_id', $request->integer('niveau_id'));
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

        $columns = ['id', 'nom', 'code'];
        foreach (['niveau_id', 'section_id', 'categorie_cours_id', 'ponderation_tj', 'ponderation_examen'] as $col) {
            if (Schema::hasColumn('matieres', $col)) {
                $columns[] = $col;
            }
        }
        $cours = $query->get($columns);

        return response()->json($cours);
    }

    public function store(StoreCoursRequest $request): JsonResponse
    {
        $cours = Matiere::create($request->validated());

        return response()->json([
            'message' => 'Cours créé avec succès',
            'data' => $cours->load(['categorieCours', 'enseignant.user', 'section', 'niveau']),
        ], 201);
    }

    public function show(Matiere $cour): JsonResponse
    {
        return response()->json([
            'data' => $cour->load([
                'categorieCours',
                'enseignant.user',
                'section',
                'niveau',

            ]),
        ]);
    }

    public function update(UpdateCoursRequest $request, Matiere $cour): JsonResponse
    {
        $cour->update($request->validated());

        return response()->json([
            'message' => 'Cours mis à jour avec succès',
            'data' => $cour->load(['categorieCours', 'enseignant.user', 'section', 'niveau']),
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
