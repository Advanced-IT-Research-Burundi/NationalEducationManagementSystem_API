<?php

namespace App\Http\Controllers\Api\HR;

use App\Exports\RH\PostesExport;
use App\Http\Controllers\Controller;
use App\Imports\RH\PostesImport;
use App\Models\Poste;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PosteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Poste::query()->with(['departement:id,nom']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        foreach (['departement_id', 'statut', 'niveau_hierarchique'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $postes = $query->withCount('employes')->orderBy('nom')->paginate((int) $request->input('per_page', 15));

        return response()->json($postes);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:postes,code'],
            'nom' => ['required', 'string', 'max:255'],
            'departement_id' => ['nullable', 'exists:departements,id'],
            'description' => ['nullable', 'string'],
            'niveau_hierarchique' => ['nullable', 'integer', 'min:1'],
            'salaire_min' => ['nullable', 'numeric', 'min:0'],
            'salaire_max' => ['nullable', 'numeric', 'min:0'],
            'statut' => ['sometimes', 'string', 'max:50'],
        ]);

        $poste = Poste::create($data);

        return response()->json(['message' => 'Poste créé avec succès', 'data' => $poste], 201);
    }

    public function show(Poste $poste): JsonResponse
    {
        return response()->json(['data' => $poste->load(['departement', 'employes'])]);
    }

    public function update(Request $request, Poste $poste): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:postes,code,' . $poste->id],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'departement_id' => ['nullable', 'exists:departements,id'],
            'description' => ['nullable', 'string'],
            'niveau_hierarchique' => ['nullable', 'integer', 'min:1'],
            'salaire_min' => ['nullable', 'numeric', 'min:0'],
            'salaire_max' => ['nullable', 'numeric', 'min:0'],
            'statut' => ['sometimes', 'string', 'max:50'],
        ]);

        $poste->update($data);

        return response()->json(['message' => 'Poste mis à jour avec succès', 'data' => $poste]);
    }

    public function destroy(Poste $poste): JsonResponse
    {
        if ($poste->employes()->exists()) {
            return response()->json(['message' => 'Impossible de supprimer ce poste car il est encore utilisé.'], 422);
        }

        $poste->delete();

        return response()->json(['message' => 'Poste supprimé avec succès']);
    }

    public function export(Request $request)
    {
        $query = Poste::query()->with(['departement:id,nom']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        foreach (['departement_id', 'statut', 'niveau_hierarchique'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return Excel::download(new PostesExport($query->orderBy('nom')->get()), 'postes-rh.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new PostesImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => 'Import postes terminé avec succès',
            'data' => [
                'created' => $import->getCreatedCount(),
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
                'errors' => $import->getErrors(),
            ],
        ]);
    }
}
