<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Fonction;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FonctionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $fonctions = Fonction::query()
            ->with(['service'])
            ->when($request->filled('service_id'), fn ($q) => $q->where('service_id', $request->integer('service_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('nom', 'like', "%{$search}%")
                        ->orWhere('grade', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom')
            ->get();

        return response()->json(['data' => $fonctions]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'code' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'grade' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service = Service::findOrFail($data['service_id']);
        $exists = Fonction::where('service_id', $service->id)->where('code', $data['code'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Ce code existe déjà pour ce service.'], 422);
        }

        $fonction = Fonction::create($data);

        return response()->json(['message' => 'Fonction créée avec succès', 'data' => $fonction], 201);
    }

    public function show(Fonction $fonction): JsonResponse
    {
        return response()->json(['data' => $fonction->load('service')]);
    }

    public function update(Request $request, Fonction $fonction): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'code' => ['sometimes', 'required', 'string', 'max:50'],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'grade' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $serviceId = $data['service_id'] ?? $fonction->service_id;
        $code = $data['code'] ?? $fonction->code;

        $exists = Fonction::where('service_id', $serviceId)
            ->where('code', $code)
            ->where('id', '!=', $fonction->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ce code existe déjà pour ce service.'], 422);
        }

        $fonction->update($data);

        return response()->json(['message' => 'Fonction mise à jour avec succès', 'data' => $fonction]);
    }

    public function destroy(Fonction $fonction): JsonResponse
    {
        if ($fonction->personnels()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette fonction car elle est encore utilisée.',
            ], 422);
        }

        $fonction->delete();

        return response()->json(['message' => 'Fonction supprimée avec succès']);
    }

    public function byService(Service $service): JsonResponse
    {
        return response()->json([
            'data' => $service->fonctions()
                ->where('is_active', true)
                ->orderBy('nom')
                ->get(),
        ]);
    }
}
