<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoursRequest;
use App\Http\Requests\UpdateCoursRequest;
use App\Models\Matiere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoursController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Matiere::query()->with([
            'categorieCours:id,nom,ordre',
            'enseignant.user:id,name',
            'section:id,nom,code',
            'niveau:id,nom,code',
            'enseignant',
            'section',
            'niveau'
        ]);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('categorie_cours_id')) {
            $query->byCategorie($request->integer('categorie_cours_id'));
        }

        if ($request->filled('section_id')) {
            $query->bySection($request->integer('section_id'));
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->integer('niveau_id'));
        }

        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', $request->integer('enseignant_id'));
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        if ($request->filled('est_principale')) {
            $query->where('est_principale', $request->boolean('est_principale'));
        }

        $cours = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($cours);
    }

    public function list(Request $request): JsonResponse
    {
        $query = Matiere::active()->orderBy('nom');

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->integer('niveau_id'));
        }

        if ($request->filled('section_id')) {
            $query->bySection($request->integer('section_id'));
        }

        if ($request->filled('categorie_cours_id')) {
            $query->byCategorie($request->integer('categorie_cours_id'));
        }

        $cours = $query->get(['id', 'nom', 'code', 'niveau_id', 'section_id', 'categorie_cours_id', 'ponderation_tj', 'ponderation_examen']);

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
