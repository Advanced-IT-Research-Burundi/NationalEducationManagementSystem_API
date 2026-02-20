<?php

namespace App\Http\Controllers\Api\Pedagogy;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInspectionRequest;
use App\Http\Requests\UpdateInspectionRequest;
use App\Models\Inspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inspection Controller
 *
 * Manages pedagogical inspections of schools.
 */
class InspectionController extends Controller
{
    public function index(): JsonResponse
    {
        $inspections = Inspection::with(['ecole', 'inspecteur'])->paginate(15);

        return response()->json([
            'message' => 'Module Pedagogy - Inspections',
            'data' => $inspections,
        ]);
    }

    public function store(StoreInspectionRequest $request): JsonResponse
    {
        $inspection = Inspection::create($request->validated());

        return response()->json([
            'message' => 'Inspection creation réussie',
            'data' => $inspection->load(['ecole', 'inspecteur']),
        ], 201);
    }

    public function show(Inspection $inspection): JsonResponse
    {
        return response()->json([
            'data' => $inspection->load(['ecole', 'inspecteur']),
        ]);
    }

    public function update(UpdateInspectionRequest $request, Inspection $inspection): JsonResponse
    {
        $inspection->update($request->validated());

        return response()->json([
            'message' => 'Inspection mise à jour réussie',
            'data' => $inspection->load(['ecole', 'inspecteur']),
        ]);
    }

    public function destroy(Inspection $inspection): JsonResponse
    {
        $inspection->delete();

        return response()->json([
            'message' => 'Inspection supprimée réussie',
        ]);
    }

    public function validateInspection(Inspection $inspection): JsonResponse
    {
        $inspection->update(['statut' => 'terminee']);

        return response()->json([
            'message' => 'Inspection validée avec succès',
            'data' => $inspection,
        ]);
    }

    public function history(string $school_id): JsonResponse
    {
        $history = Inspection::where('school_id', $school_id)
            ->with('inspecteur')
            ->orderBy('date_realisation', 'desc')
            ->get();

        return response()->json([
            'data' => $history,
        ]);
    }
}
