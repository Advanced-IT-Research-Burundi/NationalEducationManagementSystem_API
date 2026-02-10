<?php

namespace App\Http\Controllers\Api\Pedagogy;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStandardQualiteRequest;
use App\Http\Requests\UpdateStandardQualiteRequest;
use App\Models\StandardQualite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Standard Qualite Controller
 *
 * Manages educational quality standards.
 */
class StandardQualiteController extends Controller
{
    public function index(): JsonResponse
    {
        $standards = StandardQualite::all();

        return response()->json([
            'message' => 'Module Pedagogy - Standards de Qualité',
            'data' => $standards,
        ]);
    }

    public function store(StoreStandardQualiteRequest $request): JsonResponse
    {
        $standard = StandardQualite::create($request->validated());

        return response()->json([
            'message' => 'Standard de qualité créé avec succès',
            'data' => $standard,
        ], 201);
    }

    public function show(StandardQualite $standardQualite): JsonResponse
    {
        return response()->json([
            'data' => $standardQualite,
        ]);
    }

    public function update(UpdateStandardQualiteRequest $request, StandardQualite $standardQualite): JsonResponse
    {
        $standardQualite->update($request->validated());

        return response()->json([
            'message' => 'Standard de qualité mis à jour avec succès',
            'data' => $standardQualite,
        ]);
    }

    public function destroy(StandardQualite $standardQualite): JsonResponse
    {
        $standardQualite->delete();

        return response()->json([
            'message' => 'Standard de qualité supprimé avec succès',
        ]);
    }

    public function criteria(StandardQualite $standardQualite): JsonResponse
    {
        return response()->json([
            'data' => $standardQualite->criteres,
        ]);
    }
}
