<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Carriere;
use App\Models\Enseignant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Career Controller
 */
class CareerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Carriere::query()->with(['enseignant', 'ecole']);

        if ($request->has('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        if ($request->has('ecole_id')) {
            $query->where('ecole_id', $request->ecole_id);
        }

        $perPage = $request->get('per_page', 20);
        $carrieres = $query->orderBy('date_debut', 'desc')->paginate($perPage);

        return response()->json($carrieres);
    }

    public function show(Enseignant $teacher): JsonResponse
    {
        $carriere = Carriere::query()
            ->where('enseignant_id', $teacher->id)
            ->whereNull('date_fin')
            ->with(['enseignant', 'ecole'])
            ->first();

        if (! $carriere) {
            return response()->json(['message' => 'Aucune carrière active trouvée'], 404);
        }

        return response()->json(['data' => $carriere]);
    }

    public function promote(Request $request, Enseignant $teacher): JsonResponse
    {
        $request->validate([
            'nouveau_poste' => ['required', 'string', 'max:255'],
            'ecole_id' => ['nullable', 'exists:ecoles,id'],
            'date_debut' => ['required', 'date'],
        ]);

        // Close current career
        Carriere::where('enseignant_id', $teacher->id)
            ->whereNull('date_fin')
            ->update([
                'date_fin' => now(),
                'motif_fin' => 'PROMOTION',
            ]);

        // Create new career
        $carriere = Carriere::create([
            'enseignant_id' => $teacher->id,
            'poste' => $request->nouveau_poste,
            'ecole_id' => $request->ecole_id ?? $teacher->ecole_id,
            'date_debut' => $request->date_debut,
        ]);

        $carriere->load(['enseignant', 'ecole']);

        return response()->json([
            'message' => 'Promotion effectuée avec succès',
            'data' => $carriere,
        ]);
    }

    public function history(Enseignant $teacher): JsonResponse
    {
        $carrieres = Carriere::query()
            ->where('enseignant_id', $teacher->id)
            ->with('ecole')
            ->orderBy('date_debut', 'desc')
            ->paginate(20);

        return response()->json($carrieres);
    }

    public function grades(): JsonResponse
    {
        $grades = Carriere::query()
            ->select('poste')
            ->distinct()
            ->orderBy('poste')
            ->pluck('poste');

        return response()->json(['data' => $grades]);
    }
}
