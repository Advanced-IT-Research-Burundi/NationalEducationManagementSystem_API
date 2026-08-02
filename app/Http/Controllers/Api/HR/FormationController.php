<?php

namespace App\Http\Controllers\Api\HR;

use App\Exports\RH\FormationsExport;
use App\Http\Controllers\Controller;
use App\Imports\RH\FormationsImport;
use App\Models\Formation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FormationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Formation::query()->with(['employe:id,nom,prenom,matricule']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('intitule', 'like', "%{$search}%")
                    ->orWhere('organisme', 'like', "%{$search}%")
                    ->orWhere('domaine', 'like', "%{$search}%")
                    ->orWhere('numero_certificat', 'like', "%{$search}%");
            });
        }

        foreach (['employe_id', 'type', 'resultat', 'presence', 'est_certifie'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin]);
        }

        $formations = $query->orderByDesc('date_debut')->paginate((int) $request->input('per_page', 15));

        return response()->json($formations);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employe_id' => ['required', 'exists:employes,id'],
            'intitule' => ['required', 'string', 'max:255'],
            'organisme' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'domaine' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
            'duree' => ['nullable', 'string', 'max:255'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'resultat' => ['nullable', 'string', 'max:50'],
            'est_certifie' => ['sometimes', 'boolean'],
            'numero_certificat' => ['nullable', 'string', 'max:255'],
            'date_obtention' => ['nullable', 'date'],
            'date_expiration' => ['nullable', 'date'],
            'piece_jointe' => ['nullable', 'string', 'max:255'],
            'presence' => ['nullable', 'string', 'max:50'],
            'taux_presence' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commentaire' => ['nullable', 'string'],
        ]);

        $formation = Formation::create($data);

        return response()->json(['message' => 'Formation créée avec succès', 'data' => $formation], 201);
    }

    public function show(Formation $formation): JsonResponse
    {
        return response()->json(['data' => $formation->load(['employe'])]);
    }

    public function update(Request $request, Formation $formation): JsonResponse
    {
        $data = $request->validate([
            'employe_id' => ['sometimes', 'required', 'exists:employes,id'],
            'intitule' => ['sometimes', 'required', 'string', 'max:255'],
            'organisme' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'max:50'],
            'domaine' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
            'duree' => ['nullable', 'string', 'max:255'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'resultat' => ['nullable', 'string', 'max:50'],
            'est_certifie' => ['sometimes', 'boolean'],
            'numero_certificat' => ['nullable', 'string', 'max:255'],
            'date_obtention' => ['nullable', 'date'],
            'date_expiration' => ['nullable', 'date'],
            'piece_jointe' => ['nullable', 'string', 'max:255'],
            'presence' => ['nullable', 'string', 'max:50'],
            'taux_presence' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commentaire' => ['nullable', 'string'],
        ]);

        $formation->update($data);

        return response()->json(['message' => 'Formation mise à jour avec succès', 'data' => $formation]);
    }

    public function destroy(Formation $formation): JsonResponse
    {
        $formation->delete();

        return response()->json(['message' => 'Formation supprimée avec succès']);
    }

    public function export(Request $request)
    {
        $query = Formation::query()->with(['employe:id,nom,prenom,matricule']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('intitule', 'like', "%{$search}%")
                    ->orWhere('organisme', 'like', "%{$search}%")
                    ->orWhere('domaine', 'like', "%{$search}%")
                    ->orWhere('numero_certificat', 'like', "%{$search}%");
            });
        }

        foreach (['employe_id', 'type', 'resultat', 'presence', 'est_certifie'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin]);
        }

        return Excel::download(new FormationsExport($query->orderByDesc('date_debut')->get()), 'formations-rh.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new FormationsImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => 'Import formations terminé avec succès',
            'data' => [
                'created' => $import->getCreatedCount(),
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
                'errors' => $import->getErrors(),
            ],
        ]);
    }
}
