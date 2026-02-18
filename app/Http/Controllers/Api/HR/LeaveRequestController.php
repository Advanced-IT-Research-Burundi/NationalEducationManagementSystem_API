<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Conge;
use App\Models\Enseignant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Leave Request Controller
 */
class LeaveRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Conge::query()->with(['enseignant', 'approuveur']);

        if ($request->has('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $perPage = $request->get('per_page', 20);
        $conges = $query->orderBy('date_debut', 'desc')->paginate($perPage);

        return response()->json($conges);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'enseignant_id' => ['required', 'exists:enseignants,id'],
            'type' => ['required', 'in:ANNUEL,MALADIE,MATERNITE,PATERNITE,EXCEPTIONNEL'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'motif' => ['nullable', 'string'],
        ]);

        $data = $request->all();
        $data['nombre_jours'] = $this->calculateDays($request->date_debut, $request->date_fin);
        $data['statut'] = 'DEMANDE';

        $conge = Conge::create($data);
        $conge->load('enseignant');

        return response()->json([
            'message' => 'Demande de congé créée avec succès',
            'data' => $conge,
        ], 201);
    }

    public function show(Conge $conge): JsonResponse
    {
        $conge->load(['enseignant', 'approuveur']);

        return response()->json(['data' => $conge]);
    }

    public function update(Request $request, Conge $conge): JsonResponse
    {
        if ($conge->statut !== 'DEMANDE') {
            return response()->json(['message' => 'Seules les demandes en attente peuvent être modifiées'], 400);
        }

        $request->validate([
            'type' => ['sometimes', 'in:ANNUEL,MALADIE,MATERNITE,PATERNITE,EXCEPTIONNEL'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['sometimes', 'date', 'after_or_equal:date_debut'],
            'motif' => ['nullable', 'string'],
        ]);

        $data = $request->all();
        if ($request->has('date_debut') || $request->has('date_fin')) {
            $data['nombre_jours'] = $this->calculateDays(
                $request->date_debut ?? $conge->date_debut,
                $request->date_fin ?? $conge->date_fin
            );
        }

        $conge->update($data);
        $conge->load('enseignant');

        return response()->json([
            'message' => 'Demande de congé mise à jour avec succès',
            'data' => $conge,
        ]);
    }

    public function destroy(Conge $conge): JsonResponse
    {
        if ($conge->statut !== 'DEMANDE') {
            return response()->json(['message' => 'Seules les demandes en attente peuvent être supprimées'], 400);
        }

        $conge->delete();

        return response()->json(['message' => 'Demande de congé supprimée avec succès']);
    }

    public function approve(Conge $conge): JsonResponse
    {
        if ($conge->statut !== 'DEMANDE') {
            return response()->json(['message' => 'Seules les demandes en attente peuvent être approuvées'], 400);
        }

        $conge->update([
            'statut' => 'APPROUVE',
            'approuveur_id' => auth()->id(),
            'date_approbation' => now(),
        ]);

        $conge->load(['enseignant', 'approuveur']);

        return response()->json([
            'message' => 'Congé approuvé avec succès',
            'data' => $conge,
        ]);
    }

    public function reject(Request $request, Conge $conge): JsonResponse
    {
        if ($conge->statut !== 'DEMANDE') {
            return response()->json(['message' => 'Seules les demandes en attente peuvent être refusées'], 400);
        }

        $request->validate([
            'commentaire' => ['nullable', 'string'],
        ]);

        $conge->update([
            'statut' => 'REFUSE',
            'approuveur_id' => auth()->id(),
            'date_approbation' => now(),
            'commentaire_approbateur' => $request->commentaire,
        ]);

        $conge->load(['enseignant', 'approuveur']);

        return response()->json([
            'message' => 'Congé refusé',
            'data' => $conge,
        ]);
    }

    public function byTeacher(Enseignant $teacher): JsonResponse
    {
        $conges = Conge::query()
            ->where('enseignant_id', $teacher->id)
            ->orderBy('date_debut', 'desc')
            ->paginate(20);

        return response()->json($conges);
    }

    public function pending(): JsonResponse
    {
        $conges = Conge::query()
            ->where('statut', 'DEMANDE')
            ->with('enseignant')
            ->orderBy('date_debut', 'asc')
            ->paginate(20);

        return response()->json($conges);
    }

    private function calculateDays(string $dateDebut, string $dateFin): int
    {
        $debut = new \DateTime($dateDebut);
        $fin = new \DateTime($dateFin);

        return $debut->diff($fin)->days + 1;
    }
}
