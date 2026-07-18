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
        $services = Service::query()
            ->withCount('fonctions')
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('nom', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom')
            ->get();

        return response()->json(['data' => $services]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:services,code'],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service = Service::create($data);

        return response()->json(['message' => 'Service créé avec succès', 'data' => $service], 201);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json(['data' => $service->load('fonctions')]);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:services,code,' . $service->id],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service->update($data);

        return response()->json(['message' => 'Service mis à jour avec succès', 'data' => $service]);
    }

    public function destroy(Service $service): JsonResponse
    {
        if ($service->fonctions()->exists() || $service->personnels()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce service car il est encore utilisé.',
            ], 422);
        }

        $service->delete();

        return response()->json(['message' => 'Service supprimé avec succès']);
    }
}
