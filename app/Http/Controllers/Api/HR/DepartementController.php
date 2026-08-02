<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Departement::query()
            ->withCount(['employes', 'postes', 'services'])
            ->with(['parent:id,nom', 'responsable:id,name,email']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('departement_parent_id')) {
            $query->where('departement_parent_id', $request->integer('departement_parent_id'));
        }

        $departements = $query->orderBy('nom')->paginate((int) $request->input('per_page', 15));

        return response()->json($departements);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:departements,code'],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'departement_parent_id' => ['nullable', 'exists:departements,id'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'couleur' => ['nullable', 'string', 'max:20'],
            'statut' => ['sometimes', 'string', 'max:50'],
        ]);

        $departement = Departement::create($data);

        return response()->json(['message' => 'Département créé avec succès', 'data' => $departement], 201);
    }

    public function show(Departement $departement): JsonResponse
    {
        return response()->json([
            'data' => $departement->load(['parent', 'responsable', 'enfants', 'postes', 'services', 'employes']),
        ]);
    }

    public function update(Request $request, Departement $departement): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:departements,code,' . $departement->id],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'departement_parent_id' => ['nullable', 'exists:departements,id'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'couleur' => ['nullable', 'string', 'max:20'],
            'statut' => ['sometimes', 'string', 'max:50'],
        ]);

        $departement->update($data);

        return response()->json(['message' => 'Département mis à jour avec succès', 'data' => $departement]);
    }

    public function destroy(Departement $departement): JsonResponse
    {
        if ($departement->postes()->exists() || $departement->services()->exists() || $departement->employes()->exists() || $departement->enfants()->exists()) {
            return response()->json(['message' => 'Impossible de supprimer ce département car il est encore utilisé.'], 422);
        }

        $departement->delete();

        return response()->json(['message' => 'Département supprimé avec succès']);
    }

    public function hierarchy(): JsonResponse
    {
        $departements = Departement::query()
            ->withCount(['employes', 'postes'])
            ->with(['enfants' => fn ($q) => $q->withCount(['employes', 'postes'])->orderBy('nom')])
            ->whereNull('departement_parent_id')
            ->orderBy('nom')
            ->get();

        return response()->json(['data' => $departements]);
    }
}
