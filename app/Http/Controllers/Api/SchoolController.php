<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Http\Requests\SubmitSchoolRequest;
use App\Http\Requests\ValidateSchoolRequest;
use App\Http\Requests\DeactivateSchoolRequest;
use App\Models\Colline;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', School::class);

        $query = School::with(['colline', 'zone', 'commune', 'province', 'creator']);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Type filter
        if ($request->filled('type_ecole')) {
            $query->byType($request->type_ecole);
        }

        // Niveau filter
        if ($request->filled('niveau')) {
            $query->byNiveau($request->niveau);
        }

        // Province filter (additional hierarchy filter)
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        // Commune filter
        if ($request->filled('commune_id')) {
            $query->where('commune_id', $request->commune_id);
        }

        // Zone filter
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $schools = $query->latest()->paginate($request->get('per_page', 15));

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
        $data['statut'] = School::STATUS_BROUILLON; // Default status

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
     * Submit a school for validation.
     * BROUILLON → EN_ATTENTE_VALIDATION
     */
    public function submit(SubmitSchoolRequest $request, School $school)
    {
        $this->authorize('submit', $school);

        if (!$school->canSubmit()) {
            return response()->json([
                'message' => 'Cette école ne peut pas être soumise. Vérifiez que tous les champs requis sont remplis.'
            ], 422);
        }

        $school->statut = School::STATUS_EN_ATTENTE_VALIDATION;
        $school->save();

        return response()->json([
            'message' => 'École soumise pour validation avec succès',
            'school' => $school->load(['colline', 'zone', 'commune', 'province', 'creator'])
        ]);
    }

    /**
     * Validate (activate) a school.
     * EN_ATTENTE_VALIDATION → ACTIVE
     */
    public function validate(ValidateSchoolRequest $request, School $school)
    {
        $this->authorize('validate', $school);

        if (!$school->canValidate()) {
            return response()->json([
                'message' => 'Cette école ne peut pas être validée. Vérifiez que la géolocalisation est renseignée.'
            ], 422);
        }

        $school->statut = School::STATUS_ACTIVE;
        $school->validated_by = Auth::id();
        $school->validated_at = now();
        $school->save();

        return response()->json([
            'message' => 'École validée et activée avec succès',
            'school' => $school->load(['colline', 'zone', 'commune', 'province', 'creator', 'validator'])
        ]);
    }

    /**
     * Deactivate a school.
     * ACTIVE → INACTIVE
     */
    public function deactivate(DeactivateSchoolRequest $request, School $school)
    {
        $this->authorize('deactivate', $school);

        if (!$school->canDeactivate()) {
            return response()->json([
                'message' => 'Seules les écoles actives peuvent être désactivées.'
            ], 422);
        }

        $school->statut = School::STATUS_INACTIVE;
        $school->save();

        // You could store the deactivation reason in a separate table or meta field
        // For now, we just change the status

        return response()->json([
            'message' => 'École désactivée avec succès',
            'school' => $school->load(['colline', 'zone', 'commune', 'province'])
        ]);
    }
}
