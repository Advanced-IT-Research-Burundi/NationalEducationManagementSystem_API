<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCollineRequest;
use App\Http\Requests\UpdateCollineRequest;
use App\Models\Colline;
use App\Models\Zone;
use Illuminate\Http\Request;

class CollineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);

        $query = Colline::with(['zone', 'commune', 'province', 'ministere', 'pays']);

        // Filtres hiérarchiques
        if ($request->filled('zone_id')) {
            $query->byZone($request->zone_id);
        } elseif ($request->filled('commune_id')) {
            $query->byCommune($request->commune_id);
        } elseif ($request->filled('province_id')) {
            $query->byProvince($request->province_id);
        } elseif ($request->filled('ministere_id')) {
            $query->byMinistere($request->ministere_id);
        }

        // Filtre de recherche par nom
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $collines = $query->orderBy('name')->paginate($limit);

        return sendResponse($collines, 'Collines retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCollineRequest $request)
    {
        $validated = $request->validated();

        // Auto-localisation: récupérer les IDs parents depuis la zone
        $zone = Zone::findOrFail($validated['zone_id']);
        $validated['commune_id'] = $zone->commune_id;
        $validated['province_id'] = $zone->province_id;
        $validated['ministere_id'] = $zone->ministere_id;
        $validated['pays_id'] = $zone->pays_id;

        $colline = Colline::create($validated);

        return sendResponse(
            $colline->load(['zone', 'commune', 'province', 'ministere', 'pays']),
            'Colline created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $colline = Colline::with([
            'zone',
            'commune',
            'province',
            'ministere',
            'pays',
            'schools'
        ])->findOrFail($id);

        return sendResponse($colline, 'Colline retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCollineRequest $request, string $id)
    {
        $colline = Colline::findOrFail($id);
        $validated = $request->validated();

        // Si zone_id change, mettre à jour la hiérarchie
        if (isset($validated['zone_id']) && $validated['zone_id'] !== $colline->zone_id) {
            $zone = Zone::findOrFail($validated['zone_id']);
            $validated['commune_id'] = $zone->commune_id;
            $validated['province_id'] = $zone->province_id;
            $validated['ministere_id'] = $zone->ministere_id;
            $validated['pays_id'] = $zone->pays_id;
        }

        $colline->update($validated);

        return sendResponse(
            $colline->load(['zone', 'commune', 'province', 'ministere', 'pays']),
            'Colline updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $colline = Colline::findOrFail($id);

        // Vérifier s'il y a des écoles associées
        if ($colline->schools()->count() > 0) {
            return sendError(
                'Impossible de supprimer cette colline car elle contient des écoles.',
                [],
                422
            );
        }

        $colline->delete();

        return response()->json(null, 204);
    }
}
