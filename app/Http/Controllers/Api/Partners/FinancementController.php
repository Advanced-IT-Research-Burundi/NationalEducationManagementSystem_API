<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use App\Models\Financement;
use App\Models\ProjetPartenariat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Financement Controller
 */
class FinancementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Financement::query()->with('projetPartenariat.partenaire');

        if ($request->has('projet_partenariat_id')) {
            $query->where('projet_partenariat_id', $request->projet_partenariat_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->get('per_page', 20);
        $financements = $query->orderBy('date_decaissement', 'desc')->paginate($perPage);

        return response()->json($financements);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'projet_partenariat_id' => ['required', 'exists:projets_partenariat,id'],
            'montant' => ['required', 'numeric', 'min:0'],
            'date_decaissement' => ['required', 'date'],
            'type' => ['required', 'in:INITIAL,TRANCHE,COMPLEMENT,AJUSTEMENT'],
            'reference_transaction' => ['nullable', 'string', 'max:255'],
        ]);

        $financement = Financement::create($request->all());
        $financement->load('projetPartenariat.partenaire');

        return response()->json([
            'message' => 'Financement enregistré avec succès',
            'data' => $financement,
        ], 201);
    }

    public function show(Financement $financement): JsonResponse
    {
        $financement->load('projetPartenariat.partenaire');

        return response()->json(['data' => $financement]);
    }

    public function update(Request $request, Financement $financement): JsonResponse
    {
        $request->validate([
            'montant' => ['sometimes', 'numeric', 'min:0'],
            'date_decaissement' => ['sometimes', 'date'],
        ]);

        $financement->update($request->all());
        $financement->load('projetPartenariat.partenaire');

        return response()->json([
            'message' => 'Financement mis à jour avec succès',
            'data' => $financement,
        ]);
    }

    public function destroy(Financement $financement): JsonResponse
    {
        $financement->delete();

        return response()->json(['message' => 'Financement supprimé avec succès']);
    }

    public function byProject(ProjetPartenariat $projet): JsonResponse
    {
        $financements = $projet->financements()
            ->orderBy('date_decaissement', 'desc')
            ->paginate(20);

        $summary = [
            'total_finance' => $projet->financements()->sum('montant'),
            'budget_total' => $projet->budget_total,
            'taux_execution' => $projet->budget_total > 0
                ? ($projet->financements()->sum('montant') / $projet->budget_total * 100)
                : 0,
        ];

        return response()->json([
            'financements' => $financements,
            'summary' => $summary,
        ]);
    }
}
