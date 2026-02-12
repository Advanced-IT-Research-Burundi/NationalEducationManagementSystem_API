<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionExamenRequest;
use App\Http\Requests\UpdateSessionExamenRequest;
use App\Models\SessionExamen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Session Examen Controller
 *
 * Manages sessions for specific exams.
 */
class SessionExamenController extends Controller
{
    public function index(): JsonResponse
    {
        $sessions = SessionExamen::with('examen')->paginate(15);

        return response()->json([
            'message' => 'Liste des sessions d\'examen récupérée avec succès',
            'data' => $sessions
        ]);
    }

    public function store(StoreSessionExamenRequest $request): JsonResponse
    {
        $session = SessionExamen::create($request->validated());

        return response()->json([
            'message' => 'Session d\'examen créée avec succès',
            'data' => $session->load('examen')
        ], 201);
    }

    public function show(SessionExamen $session): JsonResponse
    {
        return response()->json([
            'data' => $session->load(['examen', 'centres', 'inscriptions'])
        ]);
    }

    public function update(UpdateSessionExamenRequest $request, SessionExamen $session): JsonResponse
    {
        $session->update($request->validated());

        return response()->json([
            'message' => 'Session d\'examen mise à jour avec succès',
            'data' => $session->load('examen')
        ]);
    }

    public function destroy(SessionExamen $session): JsonResponse
    {
        $session->delete();

        return response()->json([
            'message' => 'Session d\'examen supprimée avec succès'
        ]);
    }

    public function open(SessionExamen $session): JsonResponse
    {
        $session->update(['statut' => 'ouverte']);

        return response()->json([
            'message' => 'Session d\'examen ouverte avec succès',
            'data' => $session
        ]);
    }

    public function close(SessionExamen $session): JsonResponse
    {
        $session->update(['statut' => 'cloturee']);

        return response()->json([
            'message' => 'Session d\'examen clôturée avec succès',
            'data' => $session
        ]);
    }

    /**
     * Get all exam sessions (for dropdowns).
     */
    public function list(): JsonResponse
    {
        $sessions = SessionExamen::with('examen:id,libelle')->orderBy('date_debut', 'desc')->get(['id', 'examen_id', 'date_debut']);

        return response()->json($sessions);
    }
}
