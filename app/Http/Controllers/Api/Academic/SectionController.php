<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * Display a listing of sections.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Section::class);

        $query = Section::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        $sections = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($sections);
    }

    /**
     * Lightweight list for dropdowns.
     */
    public function list(): JsonResponse
    {
        $sections = Section::active()->orderBy('nom')->get(['id', 'nom', 'code']);

        return response()->json($sections);
    }

    /**
     * Store a newly created section.
     */
    public function store(StoreSectionRequest $request): JsonResponse
    {
        $section = Section::create($request->validated());

        return response()->json([
            'message' => 'Section créée avec succès',
            'section' => $section,
        ], 201);
    }

    /**
     * Display the specified section.
     */
    public function show(Section $section): JsonResponse
    {
        $this->authorize('view', $section);

        return response()->json($section->load('classes'));
    }

    /**
     * Update the specified section.
     */
    public function update(UpdateSectionRequest $request, Section $section): JsonResponse
    {
        $section->update($request->validated());

        return response()->json([
            'message' => 'Section mise à jour avec succès',
            'section' => $section,
        ]);
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section): JsonResponse
    {
        $this->authorize('delete', $section);

        if ($section->classes()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette section car elle est utilisée par des classes.',
            ], 422);
        }

        $section->delete();

        return response()->json(['message' => 'Section supprimée avec succès']);
    }
}
