<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Services\CurrentAcademicContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicContextController extends Controller
{
    public function __construct(
        private readonly CurrentAcademicContextService $contextService
    ) {}

    public function current(): JsonResponse
    {
        return response()->json($this->contextService->getContext());
    }

    public function syncTrimestre(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
        ]);

        if (array_key_exists('current_annee_scolaire_id', $validated)) {
            $this->contextService->setCurrentAcademicYear($validated['current_annee_scolaire_id']);
        }

        $trimestre = $this->contextService->syncCurrentTrimestre();

        return response()->json([
            'message' => 'Contexte trimestre synchronisé avec succès.',
            'data' => $this->contextService->getContext(),
            'trimestre' => $trimestre,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'current_trimestre_id' => ['nullable', 'exists:trimestres,id'],
        ]);

        $config = $this->contextService->setCurrentAcademicYear($validated['current_annee_scolaire_id'] ?? null);

        if (! empty($validated['current_trimestre_id'])) {
            $trimestre = \App\Models\Trimestre::query()->findOrFail($validated['current_trimestre_id']);
            if ((int) $trimestre->annee_scolaire_id !== (int) ($config->current_annee_scolaire_id ?? 0)) {
                return response()->json([
                    'message' => 'Le trimestre choisi n\'appartient pas à l\'année scolaire courante.',
                ], 422);
            }

            $config->forceFill(['current_trimestre_id' => $trimestre->id])->save();
        }

        return response()->json([
            'message' => 'Contexte académique mis à jour avec succès.',
            'data' => $this->contextService->getContext(),
        ]);
    }
}
