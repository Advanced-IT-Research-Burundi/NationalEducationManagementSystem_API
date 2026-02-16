<?php

namespace App\Http\Controllers\Api\Pedagogy;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormationRequest;
use App\Http\Requests\UpdateFormationRequest;
use App\Models\Formation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Formation Controller
 *
 * Manages training sessions for teachers and staff.
 */
class FormationController extends Controller
{
    public function index(): JsonResponse
    {
        $formations = Formation::with('formateur')->paginate(15);

        return response()->json([
            'message' => 'Module Pedagogy - Formations',
            'data' => $formations,
        ]);
    }

    public function store(StoreFormationRequest $request): JsonResponse
    {
        $formation = Formation::create($request->validated());

        return response()->json([
            'message' => 'Formation créée avec succès',
            'data' => $formation->load('formateur'),
        ], 201);
    }

    public function show(Formation $formation): JsonResponse
    {
        return response()->json([
            'data' => $formation->load(['formateur', 'participants']),
        ]);
    }

    public function update(UpdateFormationRequest $request, Formation $formation): JsonResponse
    {
        $formation->update($request->validated());

        return response()->json([
            'message' => 'Formation mise à jour avec succès',
            'data' => $formation->load('formateur'),
        ]);
    }

    public function destroy(Formation $formation): JsonResponse
    {
        $formation->delete();

        return response()->json([
            'message' => 'Formation supprimée avec succès',
        ]);
    }

    public function register(Request $request, Formation $formation): JsonResponse
    {
        $request->validate([
            'user_id' => 'required_without:eleve_id|nullable|exists:users,id',
            'eleve_id' => 'required_without:user_id|nullable|exists:eleves,id',
        ], [
            'user_id.required_without' => 'The user id or eleve id field is required.',
            'eleve_id.required_without' => 'The user id or eleve id field is required.',
        ]);

        if ($request->filled('eleve_id')) {
            $formation->participantsEleves()->syncWithoutDetaching([$request->eleve_id]);
        } else {
            $formation->participants()->syncWithoutDetaching([$request->user_id]);
        }

        return response()->json([
            'message' => 'Inscription à la formation réussie',
            'data' => $formation->load(['participants', 'participantsEleves']),
        ]);
    }

    public function participants(Formation $formation): JsonResponse
    {
        $formation->load(['participants.roles', 'participants.school', 'participantsEleves.school', 'participantsEleves.inscriptionsEleves.classe']);
        $userParticipants = $formation->participants->map(fn ($p) => [
            'id' => $p->id,
            'user_id' => $p->id,
            'eleve_id' => null,
            'nom_complet' => $p->name ?? '',
            'matricule' => null,
            'email' => $p->email ?? null,
            'role' => $p->roles->first()?->name ?? 'Participant',
            'ecole' => $p->school,
            'etablissement' => $p->school?->name ?? null,
            'classe' => null,
        ]);
        $eleveParticipants = $formation->participantsEleves->map(fn ($e) => [
            'id' => $e->id,
            'user_id' => null,
            'eleve_id' => $e->id,
            'nom_complet' => $e->nom_complet ?? ($e->prenom . ' ' . $e->nom),
            'matricule' => $e->matricule ?? null,
            'email' => null,
            'role' => 'Élève',
            'ecole' => $e->school,
            'etablissement' => $e->school?->name ?? null,
            'classe' => $e->inscriptionsEleves->sortByDesc('id')->first()?->classe ?? null,
        ]);

        return response()->json([
            'data' => $userParticipants->concat($eleveParticipants)->values(),
        ]);
    }

    public function complete(Formation $formation): JsonResponse
    {
        $formation->update(['statut' => 'terminee']);

        return response()->json([
            'message' => 'Formation marquée comme terminée',
            'data' => $formation,
        ]);
    }
}
