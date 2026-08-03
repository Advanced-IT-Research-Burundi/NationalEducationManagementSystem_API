<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\PersonnelAdministratifMouvement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonnelAdministratifMouvementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PersonnelAdministratifMouvement::query()->with([
            'personnelAdministratif.service',
            'personnelAdministratif.fonction',
            'ancienService',
            'nouveauService',
            'ancienneFonction',
            'nouvelleFonction',
            'creator',
        ]);

        if ($request->filled('personnel_administratif_id')) {
            $query->where('personnel_administratif_id', $request->integer('personnel_administratif_id'));
        }

        if ($request->filled('type_mouvement')) {
            $query->where('type_mouvement', $request->string('type_mouvement'));
        }

        return response()->json([
            'data' => $query->latest('date_mouvement')->paginate((int) $request->input('per_page', 15)),
        ]);
    }

    public function show(PersonnelAdministratifMouvement $mouvement): JsonResponse
    {
        return response()->json([
            'data' => $mouvement->load([
                'personnelAdministratif.service',
                'personnelAdministratif.fonction',
                'ancienService',
                'nouveauService',
                'ancienneFonction',
                'nouvelleFonction',
                'creator',
            ]),
        ]);
    }
}
