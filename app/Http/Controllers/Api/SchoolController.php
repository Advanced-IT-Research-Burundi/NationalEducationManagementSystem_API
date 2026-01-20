<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Models\Colline;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', School::class);

        $schools = School::with(['colline', 'zone', 'commune', 'province'])
            ->latest()
            ->paginate(15);

        return response()->json($schools);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSchoolRequest $request)
    {
        // Permission check handled in Request

        $data = $request->validated();
        
        // Auto-localization logic
        $colline = Colline::with(['zone.commune.province.ministere.pays'])->findOrFail($data['colline_id']);
        
        $data['zone_id'] = $colline->zone_id;
        $data['commune_id'] = $colline->zone->commune_id ?? null;
        $data['province_id'] = $colline->zone->commune->province_id ?? null;
        $data['ministere_id'] = $colline->zone->commune->province->ministere_id ?? null; // Might be null as ministere is often top level
        $data['pays_id'] = $colline->zone->commune->province->pays_id ?? 1; // Default to Burundi if not found logic

        $data['created_by'] = Auth::id();
        $data['statut'] = 'BROUILLON'; // Default status
        $data['validation_status'] = 'EN_ATTENTE';

        $school = School::create($data);

        return response()->json([
            'message' => 'School created successfully',
            'school' => $school
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(School $school)
    {
        $this->authorize('view', $school);
        
        return response()->json($school->load(['colline', 'zone', 'commune', 'province', 'creator', 'validator']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSchoolRequest $request, School $school)
    {
        // Permission check handled in Request

        $data = $request->validated();
        
        if (isset($data['colline_id']) && $data['colline_id'] != $school->colline_id) {
             // Re-localize if colline changed
            $colline = Colline::with(['zone.commune.province'])->findOrFail($data['colline_id']);
            $data['zone_id'] = $colline->zone_id;
            $data['commune_id'] = $colline->zone->commune_id;
            $data['province_id'] = $colline->zone->commune->province_id;
        }

        $school->update($data);

        return response()->json([
            'message' => 'School updated successfully',
            'school' => $school
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(School $school)
    {
        $this->authorize('delete', $school);
        
        $school->delete();

        return response()->json(['message' => 'School deleted successfully']);
    }

    /**
     * Validate a school (Change status to ACTIVE).
     */
    public function validateSchool(Request $request, School $school)
    {
        $this->authorize('validate', $school);

        $school->statut = 'ACTIVE';
        $school->validated_by = Auth::id();
        $school->validated_at = now();
        $school->save();

        return response()->json([
            'message' => 'School validated successfully',
            'school' => $school
        ]);
    }
}
