<?php

namespace App\Http\Controllers\Api\Schools;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeactivateSchoolRequest;
use App\Http\Requests\SubmitSchoolRequest;
use App\Http\Requests\ValidateSchoolRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SchoolWorkflowController extends Controller
{
    /**
     * Submit a school for validation.
     * BROUILLON -> EN_ATTENTE_VALIDATION
     */
    public function submit(SubmitSchoolRequest $request, School $school): JsonResponse
    {
        $this->authorize('submit', $school);

        if (! $school->canSubmit()) {
            return response()->json([
                'message' => 'Cette école ne peut pas être soumise. Vérifiez que tous les champs requis sont remplis.',
            ], 422);
        }

        $school->statut = School::STATUS_EN_ATTENTE_VALIDATION;
        $school->save();

        return response()->json([
            'message' => 'École soumise pour validation avec succès',
            'school' => $school->load(['colline', 'zone', 'commune', 'province', 'creator']),
        ]);
    }

    /**
     * Validate (activate) a school.
     * EN_ATTENTE_VALIDATION -> ACTIVE
     */
    public function validate(ValidateSchoolRequest $request, School $school): JsonResponse
    {
        $this->authorize('validate', $school);

        if (! $school->canValidate()) {
            return response()->json([
                'message' => 'Cette école ne peut pas être validée. Vérifiez que la géolocalisation est renseignée.',
            ], 422);
        }

        $school->statut = School::STATUS_ACTIVE;
        $school->validated_by = Auth::id();
        $school->validated_at = now();
        $school->save();

        return response()->json([
            'message' => 'École validée et activée avec succès',
            'school' => $school->load(['colline', 'zone', 'commune', 'province', 'creator', 'validator']),
        ]);
    }

    /**
     * Deactivate a school.
     * ACTIVE -> INACTIVE
     */
    public function deactivate(DeactivateSchoolRequest $request, School $school): JsonResponse
    {
        $this->authorize('deactivate', $school);

        if (! $school->canDeactivate()) {
            return response()->json([
                'message' => 'Seules les écoles actives peuvent être désactivées.',
            ], 422);
        }

        $school->statut = School::STATUS_INACTIVE;
        $school->deactivated_by = Auth::id();
        $school->deactivated_at = now();
        $school->deactivation_reason = $request->input('reason');
        $school->save();

        return response()->json([
            'message' => 'École désactivée avec succès',
            'school' => $school->load(['colline', 'zone', 'commune', 'province']),
        ]);
    }

    /**
     * Reactivate a school.
     * INACTIVE -> ACTIVE
     */
    public function reactivate(School $school): JsonResponse
    {
        $this->authorize('validate', $school);

        if ($school->statut !== School::STATUS_INACTIVE) {
            return response()->json([
                'message' => 'Seules les écoles inactives peuvent être réactivées.',
            ], 422);
        }

        $school->statut = School::STATUS_ACTIVE;
        $school->reactivated_by = Auth::id();
        $school->reactivated_at = now();
        $school->save();

        return response()->json([
            'message' => 'École réactivée avec succès',
            'school' => $school->load(['colline', 'zone', 'commune', 'province']),
        ]);
    }
}
