<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()
            ->with(['departement:id,nom', 'responsable:id,name,email'])
            ->withCount('employes');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('bureau', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        foreach (['departement_id', 'responsable_id', 'statut'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $services = $query->orderBy('nom')->paginate((int) $request->input('per_page', 15));

        return response()->json($services);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:services,code'],
            'nom' => ['required', 'string', 'max:255'],
            'departement_id' => ['nullable', 'exists:departements,id'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'bureau' => ['nullable', 'string', 'max:255'],
            'statut' => ['sometimes', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service = Service::create($data);

        return response()->json(['message' => 'Service créé avec succès', 'data' => $service], 201);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json([
            'data' => $service->load(['departement', 'responsable', 'employes']),
        ]);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:services,code,' . $service->id],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'departement_id' => ['nullable', 'exists:departements,id'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'bureau' => ['nullable', 'string', 'max:255'],
            'statut' => ['sometimes', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service->update($data);

        return response()->json(['message' => 'Service mis à jour avec succès', 'data' => $service]);
    }

    public function destroy(Service $service): JsonResponse
    {
        if ($service->employes()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce service car il est encore utilisé.',
            ], 422);
        }

        $service->delete();

        return response()->json(['message' => 'Service supprimé avec succès']);
    }
}
