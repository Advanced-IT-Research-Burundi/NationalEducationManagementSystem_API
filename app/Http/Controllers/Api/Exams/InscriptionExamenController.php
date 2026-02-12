<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInscriptionExamenRequest;
use App\Http\Requests\UpdateInscriptionExamenRequest;
use App\Models\InscriptionExamen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inscription Examen Controller
 *
 * Manages student registrations for exams.
 */
class InscriptionExamenController extends Controller
{
    public function index(): JsonResponse
    {
        $inscriptions = InscriptionExamen::with(['eleve', 'session', 'centre'])->paginate(15);

        return response()->json([
            'message' => 'Liste des inscriptions d\'examen récupérée avec succès',
            'data' => $inscriptions
        ]);
    }

    public function store(StoreInscriptionExamenRequest $request): JsonResponse
    {
        $inscription = InscriptionExamen::create($request->validated());

        return response()->json([
            'message' => 'Inscription d\'examen créée avec succès',
            'data' => $inscription->load(['eleve', 'session', 'centre'])
        ], 201);
    }

    public function show(InscriptionExamen $inscription): JsonResponse
    {
        return response()->json([
            'data' => $inscription->load(['eleve', 'session', 'centre', 'resultats', 'certificat'])
        ]);
    }

    public function update(UpdateInscriptionExamenRequest $request, InscriptionExamen $inscription): JsonResponse
    {
        $inscription->update($request->validated());

        return response()->json([
            'message' => 'Inscription d\'examen mise à jour avec succès',
            'data' => $inscription->load(['eleve', 'session', 'centre'])
        ]);
    }

    public function destroy(InscriptionExamen $inscription): JsonResponse
    {
        $inscription->delete();

        return response()->json([
            'message' => 'Inscription d\'examen supprimée avec succès'
        ]);
    }

    public function validateInscription(InscriptionExamen $inscription): JsonResponse
    {
        $inscription->update(['statut' => 'inscrit']);

        return response()->json([
            'message' => 'Inscription validée avec succès',
            'data' => $inscription
        ]);
    }

    public function generateAnonymat(InscriptionExamen $inscription): JsonResponse
    {
        $inscription->update(['numero_anonymat' => 'ANON-' . strtoupper(uniqid())]);

        return response()->json([
            'message' => 'Numéro d\'anonymat généré avec succès',
            'data' => $inscription
        ]);
    }
}
