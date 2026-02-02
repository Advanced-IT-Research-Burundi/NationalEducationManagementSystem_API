<?php

namespace App\Http\Controllers\Api\Schools;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class SchoolTypeController extends Controller
{
    /**
     * Display a listing of school types and levels.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'types' => [
                ['value' => School::TYPE_PUBLIQUE, 'label' => 'Publique'],
                ['value' => School::TYPE_PRIVEE, 'label' => 'Privée'],
                ['value' => School::TYPE_ECC, 'label' => 'ECC (Église Catholique)'],
                ['value' => School::TYPE_AUTRE, 'label' => 'Autre'],
            ],
            'niveaux' => [
                ['value' => School::NIVEAU_FONDAMENTAL, 'label' => 'Fondamental'],
                ['value' => School::NIVEAU_POST_FONDAMENTAL, 'label' => 'Post-Fondamental'],
                ['value' => School::NIVEAU_SECONDAIRE, 'label' => 'Secondaire'],
                ['value' => School::NIVEAU_SUPERIEUR, 'label' => 'Supérieur'],
            ],
            'statuts' => [
                ['value' => School::STATUS_BROUILLON, 'label' => 'Brouillon'],
                ['value' => School::STATUS_EN_ATTENTE_VALIDATION, 'label' => 'En attente de validation'],
                ['value' => School::STATUS_ACTIVE, 'label' => 'Active'],
                ['value' => School::STATUS_INACTIVE, 'label' => 'Inactive'],
            ],
        ]);
    }

    /**
     * Display the specified school type details.
     */
    public function show(string $type): JsonResponse
    {
        $types = [
            School::TYPE_PUBLIQUE => [
                'value' => School::TYPE_PUBLIQUE,
                'label' => 'Publique',
                'description' => 'École gérée par l\'État',
            ],
            School::TYPE_PRIVEE => [
                'value' => School::TYPE_PRIVEE,
                'label' => 'Privée',
                'description' => 'École privée laïque',
            ],
            School::TYPE_ECC => [
                'value' => School::TYPE_ECC,
                'label' => 'ECC',
                'description' => 'École Catholique Conventionnée',
            ],
            School::TYPE_AUTRE => [
                'value' => School::TYPE_AUTRE,
                'label' => 'Autre',
                'description' => 'Autre type d\'établissement',
            ],
        ];

        if (! isset($types[$type])) {
            return response()->json(['message' => 'Type not found'], 404);
        }

        return response()->json($types[$type]);
    }
}
