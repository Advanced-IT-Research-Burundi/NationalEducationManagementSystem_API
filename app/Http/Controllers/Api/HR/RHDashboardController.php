<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Employe;
use App\Models\Formation;
use App\Models\Poste;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RHDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $baseEmployes = Employe::query();

        $totalEmployes = (clone $baseEmployes)->count();
        $actifs = (clone $baseEmployes)->where('statut', Employe::STATUT_ACTIF)->count();
        $enConge = (clone $baseEmployes)->where('statut', Employe::STATUT_CONGE)->count();
        $suspendus = (clone $baseEmployes)->where('statut', Employe::STATUT_SUSPENDU)->count();
        $demissionnaires = (clone $baseEmployes)->where('statut', Employe::STATUT_DEMISSIONNAIRE)->count();
        $retraites = (clone $baseEmployes)->where('statut', Employe::STATUT_RETRAITE)->count();
        $nouveaux = (clone $baseEmployes)->whereDate('date_embauche', '>=', now()->subDays(30))->count();
        $femmes = (clone $baseEmployes)->whereRaw('upper(sexe) = ?', ['F'])->count();
        $hommes = (clone $baseEmployes)->whereRaw('upper(sexe) = ?', ['M'])->count();

        $departements = Departement::query()
            ->withCount('employes')
            ->orderBy('nom')
            ->get(['id', 'nom', 'couleur']);

        $services = Service::query()
            ->withCount('employes')
            ->orderBy('nom')
            ->get(['id', 'nom', 'statut']);

        $postes = Poste::query()
            ->withCount('employes')
            ->orderBy('nom')
            ->get(['id', 'nom', 'statut']);

        $anniversaires = Employe::query()
            ->select('id', 'nom', 'prenom', 'date_naissance', 'photo_path')
            ->whereNotNull('date_naissance')
            ->whereMonth('date_naissance', now()->month)
            ->orderBy('date_naissance')
            ->limit(10)
            ->get();

        $contrats = Employe::query()
            ->select('id', 'nom', 'prenom', 'date_fin_contrat', 'poste_id')
            ->whereNotNull('date_fin_contrat')
            ->whereBetween('date_fin_contrat', [now()->toDateString(), now()->addDays(90)->toDateString()])
            ->with('poste:id,nom')
            ->orderBy('date_fin_contrat')
            ->limit(10)
            ->get();

        $formations = Formation::query();
        $formationsSuivies = (clone $formations)->count();
        $certifications = (clone $formations)->where('est_certifie', true)->count();
        $participation = (clone $formations)->avg('taux_presence');

        $repartitionSexe = [
            ['label' => 'Hommes', 'total' => (int) $hommes],
            ['label' => 'Femmes', 'total' => (int) $femmes],
        ];

        $repartitionDept = $departements->map(fn ($d) => [
            'id' => $d->id,
            'nom' => $d->nom,
            'total' => (int) $d->employes_count,
            'couleur' => $d->couleur,
        ]);

        $repartitionServices = $services->map(fn ($s) => [
            'id' => $s->id,
            'nom' => $s->nom,
            'total' => (int) $s->employes_count,
        ]);

        $repartitionPostes = $postes->map(fn ($p) => [
            'id' => $p->id,
            'nom' => $p->nom,
            'total' => (int) $p->employes_count,
        ]);

        $newByMonth = Employe::query()
            ->select(DB::raw('DATE_FORMAT(date_embauche, "%Y-%m") as mois'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('date_embauche')
            ->groupBy('mois')
            ->orderBy('mois', 'desc')
            ->limit(6)
            ->get()
            ->map(fn ($row) => ['label' => $row->mois, 'total' => (int) $row->total])
            ->reverse()
            ->values();

        return response()->json([
            'data' => [
                'total_employes' => $totalEmployes,
                'employes_actifs' => $actifs,
                'employes_en_conge' => $enConge,
                'employes_suspendus' => $suspendus,
                'employes_demissionnaires' => $demissionnaires,
                'employes_retraites' => $retraites,
                'nouveaux_employes' => $nouveaux,
                'repartition_par_departement' => $repartitionDept,
                'repartition_par_service' => $repartitionServices,
                'repartition_par_poste' => $repartitionPostes,
                'repartition_sexe' => $repartitionSexe,
                'anniversaires_du_mois' => $anniversaires,
                'contrats_expirant' => $contrats,
                'formations_suivies' => (int) $formationsSuivies,
                'certifications_obtenues' => (int) $certifications,
                'taux_participation_formations' => round((float) $participation, 2),
                'nouveaux_employes_par_mois' => $newByMonth,
            ],
        ]);
    }
}
