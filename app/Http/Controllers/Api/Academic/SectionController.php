<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Models\Niveau;
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

        $query = Section::query()->with([
            'typeScolaire:id,nom',
            'niveau:id,nom,code,type_id',
            'niveau.typeScolaire:id,nom',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($builder) use ($search) {
                $builder->search($search)
                    ->orWhereHas('niveau', function ($niveauQuery) use ($search) {
                        $niveauQuery->where('nom', 'LIKE', "%{$search}%")
                            ->orWhere('code', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        if ($request->filled('type_id')) {
            $query->where('type_id', $request->integer('type_id'));
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->integer('niveau_id'));
        }

        $sections = $query->latest()->paginate($request->integer('per_page', 15));

        return sendResponse($sections, 'Sections récupérées avec succès');
    }

    /**
     * Lightweight list for dropdowns.
     */
    public function list(Request $request): JsonResponse
    {
        $query = Section::query()->active()->with('niveau:id,nom,code,type_id')->orderBy('nom');

        if ($request->filled('type_id')) {
            $query->where('type_id', $request->integer('type_id'));
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->integer('niveau_id'));
        }

        $sections = $query->get(['id', 'nom', 'code', 'type_id', 'niveau_id']);

        return response()->json($sections);
    }

    /**
     * Store a newly created section.
     */
    public function store(StoreSectionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['type_id'] = isset($data['niveau_id'])
            ? Niveau::query()->find($data['niveau_id'])?->type_id
            : null;

        $section = Section::create($data);

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

        return response()->json($section->load([
            'classes',
            'typeScolaire:id,nom',
            'niveau:id,nom,code,type_id',
            'niveau.typeScolaire:id,nom',
        ]));
    }

    /**
     * Update the specified section.
     */
    public function update(UpdateSectionRequest $request, Section $section): JsonResponse
    {
        $data = $request->validated();
        $data['type_id'] = isset($data['niveau_id'])
            ? Niveau::query()->find($data['niveau_id'])?->type_id
            : null;

        $section->update($data);

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
