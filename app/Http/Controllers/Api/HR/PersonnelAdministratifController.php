<?php

namespace App\Http\Controllers\Api\HR;

use App\Exports\RH\PersonnelAdministratifExport;
use App\Http\Controllers\Controller;
use App\Models\Fonction;
use App\Models\PersonnelAdministratif;
use App\Models\PersonnelAdministratifMouvement;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PersonnelAdministratifController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PersonnelAdministratif::query()->with(['service', 'fonction', 'user', 'creator']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        foreach (['service_id', 'fonction_id', 'statut', 'type_personnel'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $personnels = $query->latest()->paginate((int) $request->input('per_page', 15));

        return response()->json($personnels);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);

        if (! empty($data['fonction_id'])) {
            $this->ensureFonctionMatchesService($data['service_id'] ?? null, $data['fonction_id']);
        }

        DB::beginTransaction();

        try {
            $personnel = PersonnelAdministratif::create([
                ...$data,
                'created_by' => Auth::id(),
            ]);

            $this->recordMovement($personnel, null, $data, 'CREATION');

            DB::commit();

            return response()->json([
                'message' => 'Personnel administratif créé avec succès',
                'data' => $personnel->load(['service', 'fonction', 'user']),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(PersonnelAdministratif $personnelAdministratif): JsonResponse
    {
        return response()->json([
            'data' => $personnelAdministratif->load(['service', 'fonction', 'user', 'creator', 'mouvements.ancienService', 'mouvements.nouveauService', 'mouvements.ancienneFonction', 'mouvements.nouvelleFonction']),
        ]);
    }

    public function update(Request $request, PersonnelAdministratif $personnelAdministratif): JsonResponse
    {
        $data = $this->validatePayload($request, $personnelAdministratif);

        if (! empty($data['fonction_id'])) {
            $this->ensureFonctionMatchesService($data['service_id'] ?? $personnelAdministratif->service_id, $data['fonction_id']);
        }

        $before = $personnelAdministratif->replicate();

        DB::beginTransaction();

        try {
            $personnelAdministratif->update($data);

            if (
                $before->service_id !== $personnelAdministratif->service_id ||
                $before->fonction_id !== $personnelAdministratif->fonction_id
            ) {
                $this->recordMovement(
                    $personnelAdministratif,
                    $before,
                    $data,
                    'MUTATION'
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Personnel administratif mis à jour avec succès',
                'data' => $personnelAdministratif->load(['service', 'fonction', 'user']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(PersonnelAdministratif $personnelAdministratif): JsonResponse
    {
        $personnelAdministratif->delete();

        return response()->json(['message' => 'Personnel administratif supprimé avec succès']);
    }

    public function statistics(Request $request): JsonResponse
    {
        $baseQuery = PersonnelAdministratif::query();

        foreach (['service_id', 'fonction_id', 'type_personnel'] as $field) {
            if ($request->filled($field)) {
                $baseQuery->where($field, $request->input($field));
            }
        }

        $total = (clone $baseQuery)->count();
        $parStatut = (clone $baseQuery)->selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $parService = (clone $baseQuery)->with('service')->selectRaw('service_id, COUNT(*) as total')->groupBy('service_id')->get()->map(function ($row) {
            return [
                'service_id' => $row->service_id,
                'service' => $row->service?->nom,
                'total' => (int) $row->total,
            ];
        })->values();
        $parFonction = (clone $baseQuery)->with('fonction')->selectRaw('fonction_id, COUNT(*) as total')->groupBy('fonction_id')->get()->map(function ($row) {
            return [
                'fonction_id' => $row->fonction_id,
                'fonction' => $row->fonction?->nom,
                'total' => (int) $row->total,
            ];
        })->values();
        $parType = (clone $baseQuery)->selectRaw('type_personnel, COUNT(*) as total')->groupBy('type_personnel')->pluck('total', 'type_personnel');

        return response()->json([
            'data' => [
                'total' => $total,
                'actifs' => (int) ($parStatut[PersonnelAdministratif::STATUT_ACTIF] ?? 0),
                'inactifs' => (int) ($parStatut[PersonnelAdministratif::STATUT_INACTIF] ?? 0),
                'suspendus' => (int) ($parStatut[PersonnelAdministratif::STATUT_SUSPENDU] ?? 0),
                'retraites' => (int) ($parStatut[PersonnelAdministratif::STATUT_RETRAITE] ?? 0),
                'par_statut' => $parStatut,
                'par_service' => $parService,
                'par_fonction' => $parFonction,
                'par_type_personnel' => $parType,
                'services_count' => Service::query()->where('is_active', true)->count(),
                'fonctions_count' => Fonction::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $query = PersonnelAdministratif::query()->with(['service', 'fonction']);

        foreach (['search', 'service_id', 'fonction_id', 'statut', 'type_personnel'] as $field) {
            if ($request->filled($field)) {
                if ($field === 'search') {
                    $search = trim((string) $request->search);
                    $query->where(function ($q) use ($search) {
                        $q->where('matricule', 'like', "%{$search}%")
                            ->orWhere('nom', 'like', "%{$search}%")
                            ->orWhere('prenom', 'like', "%{$search}%");
                    });
                } else {
                    $query->where($field, $request->input($field));
                }
            }
        }

        $personnels = $query->orderBy('nom')->get();

        return Excel::download(
            new PersonnelAdministratifExport($personnels),
            'personnel-administratif.xlsx'
        );
    }

    public function mouvements(PersonnelAdministratif $personnelAdministratif): JsonResponse
    {
        return response()->json([
            'data' => $personnelAdministratif->mouvements()
                ->with(['ancienService', 'nouveauService', 'ancienneFonction', 'nouvelleFonction', 'creator'])
                ->latest('date_mouvement')
                ->get(),
        ]);
    }

    protected function validatePayload(Request $request, ?PersonnelAdministratif $personnelAdministratif = null): array
    {
        $payload = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'fonction_id' => ['nullable', 'exists:fonctions,id'],
            'matricule' => [
                $personnelAdministratif ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('personnel_administratifs', 'matricule')->ignore($personnelAdministratif?->id),
            ],
            'nom' => [$personnelAdministratif ? 'sometimes' : 'required', 'string', 'max:255'],
            'prenom' => [$personnelAdministratif ? 'sometimes' : 'required', 'string', 'max:255'],
            'sexe' => ['nullable', 'string', 'max:10'],
            'date_naissance' => ['nullable', 'date'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('personnel_administratifs', 'email')->ignore($personnelAdministratif?->id),
            ],
            'type_personnel' => ['nullable', 'string', 'max:255'],
            'statut' => ['nullable', 'string', 'max:50'],
            'date_recrutement' => ['nullable', 'date'],
            'adresse' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        return array_filter($payload, fn ($value) => ! is_null($value));
    }

    protected function ensureFonctionMatchesService(?int $serviceId, int $fonctionId): void
    {
        $fonction = Fonction::query()->findOrFail($fonctionId);

        if ($serviceId && (int) $fonction->service_id !== (int) $serviceId) {
            abort(422, 'La fonction sélectionnée n’appartient pas au service choisi.');
        }
    }

    protected function recordMovement(
        PersonnelAdministratif $personnel,
        ?PersonnelAdministratif $before,
        array $payload,
        string $typeMouvement
    ): void {
        PersonnelAdministratifMouvement::create([
            'personnel_administratif_id' => $personnel->id,
            'ancien_service_id' => $before?->service_id,
            'nouveau_service_id' => $payload['service_id'] ?? $personnel->service_id,
            'ancienne_fonction_id' => $before?->fonction_id,
            'nouvelle_fonction_id' => $payload['fonction_id'] ?? $personnel->fonction_id,
            'type_mouvement' => $typeMouvement,
            'date_mouvement' => $payload['date_recrutement'] ?? now()->toDateString(),
            'motif' => $payload['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);
    }
}
