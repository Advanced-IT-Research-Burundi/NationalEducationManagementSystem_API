<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Enseignant;
use App\Models\Presence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Attendance Controller
 */
class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Presence::query()->with('enseignant');

        if ($request->has('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('date', [$request->date_debut, $request->date_fin]);
        }

        $perPage = $request->get('per_page', 20);
        $presences = $query->orderBy('date', 'desc')->paginate($perPage);

        return response()->json($presences);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'enseignant_id' => ['required', 'exists:enseignants,id'],
            'date' => ['required', 'date'],
            'heure_arrivee' => ['nullable', 'date_format:H:i'],
            'heure_depart' => ['nullable', 'date_format:H:i'],
            'statut' => ['required', 'in:PRESENT,ABSENT_JUSTIFIE,ABSENT_NON_JUSTIFIE,RETARD,CONGE'],
            'justificatif' => ['nullable', 'string'],
        ]);

        $presence = Presence::create($request->all());
        $presence->load('enseignant');

        return response()->json([
            'message' => 'Présence enregistrée avec succès',
            'data' => $presence,
        ], 201);
    }

    public function show(Presence $presence): JsonResponse
    {
        $presence->load('enseignant');

        return response()->json(['data' => $presence]);
    }

    public function byTeacher(Enseignant $teacher, Request $request): JsonResponse
    {
        $query = Presence::query()
            ->where('enseignant_id', $teacher->id);

        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('date', [$request->date_debut, $request->date_fin]);
        }

        $presences = $query->orderBy('date', 'desc')->paginate(20);

        return response()->json($presences);
    }

    public function bySchool(string $school, Request $request): JsonResponse
    {
        $query = Presence::query()
            ->whereHas('enseignant', function ($q) use ($school) {
                $q->where('ecole_id', $school);
            })
            ->with('enseignant');

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        $presences = $query->orderBy('date', 'desc')->paginate(20);

        return response()->json($presences);
    }

    public function summary(Request $request): JsonResponse
    {
        $query = Presence::query();

        if ($request->has('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('date', [$request->date_debut, $request->date_fin]);
        }

        $summary = [
            'total' => $query->count(),
            'by_statut' => Presence::select('statut', DB::raw('count(*) as count'))
                ->groupBy('statut')
                ->get(),
            'taux_presence' => $query->where('statut', 'PRESENT')->count() / max($query->count(), 1) * 100,
        ];

        return response()->json(['data' => $summary]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'presences' => ['required', 'array'],
            'presences.*.enseignant_id' => ['required', 'exists:enseignants,id'],
            'presences.*.date' => ['required', 'date'],
            'presences.*.statut' => ['required', 'in:PRESENT,ABSENT_JUSTIFIE,ABSENT_NON_JUSTIFIE,RETARD,CONGE'],
        ]);

        $presences = collect($request->presences)->map(function ($data) {
            return Presence::create($data);
        });

        return response()->json([
            'message' => 'Présences enregistrées avec succès',
            'data' => $presences,
        ], 201);
    }
}
