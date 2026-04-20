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
            'niveau.typeScolaire:id,nom',
        ]);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->integer('niveau_id'));
        }

        $matieres = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($matieres);
    }

    /**
     * Lightweight list for dropdowns.
     */
    public function list(Request $request): JsonResponse
    {
        $query = Matiere::active()->orderBy('nom');

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->integer('niveau_id'));
        }

        $matieres = $query->get(['id', 'nom', 'code', 'niveau_id', 'section_id']);

        return response()->json($matieres);
    }

    /**
     * Store a newly created matiere.
     */
    public function store(StoreMatiereRequest $request): JsonResponse
    {
        $matiere = Matiere::create($request->validated());

        return response()->json([
            'message' => 'Matière créée avec succès',
            'matiere' => $matiere,
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
            'niveau.typeScolaire:id,nom',
        ]));
    }

    /**
     * Update the specified matiere.
     */
    public function update(UpdateMatiereRequest $request, Matiere $matiere): JsonResponse
    {
        $matiere->update($request->validated());

        return response()->json([
            'message' => 'Matière mise à jour avec succès',
            'matiere' => $matiere,
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
