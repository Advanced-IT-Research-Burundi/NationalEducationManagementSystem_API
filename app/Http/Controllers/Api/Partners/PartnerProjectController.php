<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use App\Models\Partenaire;
use App\Models\ProjetPartenariat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Partner Project Controller
 */
class PartnerProjectController extends Controller
{
    // Partenaires CRUD
    public function indexPartenaires(Request $request): JsonResponse
    {
        $query = Partenaire::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('search')) {
            $query->where('nom', 'like', '%'.$request->search.'%');
        }

        $perPage = $request->get('per_page', 20);
        $partenaires = $query->withCount('projets')->paginate($perPage);

        return response()->json($partenaires);
    }

    public function storePartenaire(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:PTF,ONG,UNIVERSITE,ENTREPRISE,AUTRE'],
            'pays' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email'],
            'contact_telephone' => ['nullable', 'string', 'max:20'],
            'date_debut_partenariat' => ['nullable', 'date'],
        ]);

        $partenaire = Partenaire::create($request->all());

        return response()->json([
            'message' => 'Partenaire créé avec succès',
            'data' => $partenaire,
        ], 201);
    }

    public function showPartenaire(Partenaire $partenaire): JsonResponse
    {
        $partenaire->load(['projets.financements']);

        return response()->json(['data' => $partenaire]);
    }

    public function updatePartenaire(Request $request, Partenaire $partenaire): JsonResponse
    {
        $request->validate([
            'nom' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:PTF,ONG,UNIVERSITE,ENTREPRISE,AUTRE'],
            'statut' => ['sometimes', 'in:ACTIF,INACTIF,SUSPENDU'],
        ]);

        $partenaire->update($request->all());

        return response()->json([
            'message' => 'Partenaire mis à jour avec succès',
            'data' => $partenaire,
        ]);
    }

    public function destroyPartenaire(Partenaire $partenaire): JsonResponse
    {
        $partenaire->delete();

        return response()->json(['message' => 'Partenaire supprimé avec succès']);
    }

    // Projets CRUD
    public function indexProjets(Request $request): JsonResponse
    {
        $query = ProjetPartenariat::query()->with(['partenaire', 'responsable']);

        if ($request->has('partenaire_id')) {
            $query->where('partenaire_id', $request->partenaire_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('domaine')) {
            $query->where('domaine', $request->domaine);
        }

        $perPage = $request->get('per_page', 20);
        $projets = $query->paginate($perPage);

        return response()->json($projets);
    }

    public function storeProjet(Request $request): JsonResponse
    {
        $request->validate([
            'partenaire_id' => ['required', 'exists:partenaires,id'],
            'nom' => ['required', 'string', 'max:255'],
            'domaine' => ['required', 'in:INFRASTRUCTURE,EQUIPEMENT,FORMATION,RECHERCHE,AUTRE'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
            'budget_total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $projet = ProjetPartenariat::create($request->all());
        $projet->load('partenaire');

        return response()->json([
            'message' => 'Projet créé avec succès',
            'data' => $projet,
        ], 201);
    }

    public function showProjet(ProjetPartenariat $projet): JsonResponse
    {
        $projet->load(['partenaire', 'responsable', 'financements']);

        return response()->json(['data' => $projet]);
    }

    public function updateProjet(Request $request, ProjetPartenariat $projet): JsonResponse
    {
        $request->validate([
            'nom' => ['sometimes', 'string', 'max:255'],
            'statut' => ['sometimes', 'in:PLANIFIE,EN_COURS,TERMINE,SUSPENDU,ANNULE'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $projet->update($request->all());
        $projet->load('partenaire');

        return response()->json([
            'message' => 'Projet mis à jour avec succès',
            'data' => $projet,
        ]);
    }

    public function destroyProjet(ProjetPartenariat $projet): JsonResponse
    {
        $projet->delete();

        return response()->json(['message' => 'Projet supprimé avec succès']);
    }

    // Statistiques
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_partenaires' => Partenaire::where('statut', 'ACTIF')->count(),
            'total_projets' => ProjetPartenariat::count(),
            'projets_en_cours' => ProjetPartenariat::where('statut', 'EN_COURS')->count(),
            'by_type' => Partenaire::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get(),
            'by_domaine' => ProjetPartenariat::select('domaine', DB::raw('count(*) as count'))
                ->groupBy('domaine')
                ->get(),
            'budget_total' => ProjetPartenariat::sum('budget_total'),
        ];

        return response()->json(['data' => $stats]);
    }
}
