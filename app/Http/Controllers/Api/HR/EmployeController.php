<?php

namespace App\Http\Controllers\Api\HR;

use App\Exports\RH\EmployesExport;
use App\Http\Controllers\Controller;
use App\Imports\RH\EmployesImport;
use App\Models\Colline;
use App\Models\Commune;
use App\Models\Employe;
use App\Models\Pays;
use App\Models\Province;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class EmployeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Employe::query()->with([
            'departement:id,nom,couleur',
            'poste:id,nom',
            'service:id,nom',
            'user:id,name,email',
            'superieur:id,nom,prenom',
            'pays:id,name',
            'province:id,name',
            'commune:id,name',
            'zone:id,name',
            'colline:id,name',
        ]);

        if ($request->route('service')) {
            $service = $request->route('service');
            $serviceId = is_object($service) ? $service->id : $service;

            if (! $request->filled('service_id')) {
                $query->where('service_id', $serviceId);
            }
        }

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

        foreach (['departement_id', 'poste_id', 'service_id', 'statut', 'temps_travail', 'type_contrat', 'is_archived'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        $employes = $query->orderByDesc('id')->paginate((int) $request->input('per_page', 15));

        return response()->json($employes);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['matricule'] = $data['matricule'] ?? $this->generateMatricule($data['nom'], $data['prenom']);

        $employe = Employe::create($data);

        return response()->json(['message' => 'Employé créé avec succès', 'data' => $employe->load(['departement', 'poste', 'service', 'user', 'superieur'])], 201);
    }

    public function show(Employe $employe): JsonResponse
    {
        return response()->json([
            'data' => $employe->load([
                'departement',
                'poste',
                'service',
                'user',
                'superieur',
                'createur',
                'modificateur',
                'documents',
                'formations',
                'pays',
                'province',
                'commune',
                'zone',
                'colline',
            ]),
        ]);
    }

    public function update(Request $request, Employe $employe): JsonResponse
    {
        $data = $this->validatePayload($request, $employe);
        $data['updated_by'] = Auth::id();

        $employe->update($data);

        return response()->json(['message' => 'Employé mis à jour avec succès', 'data' => $employe->load(['departement', 'poste', 'service', 'user', 'superieur'])]);
    }

    public function destroy(Employe $employe): JsonResponse
    {
        $employe->update(['is_archived' => true, 'statut' => Employe::STATUT_DEMISSIONNAIRE, 'updated_by' => Auth::id()]);
        $employe->delete();

        return response()->json(['message' => 'Employé archivé avec succès']);
    }

    public function archive(Employe $employe): JsonResponse
    {
        $employe->update(['is_archived' => true, 'updated_by' => Auth::id()]);

        return response()->json(['message' => 'Employé archivé avec succès']);
    }

    public function restore(string $id): JsonResponse
    {
        $employe = Employe::withTrashed()->findOrFail($id);
        $employe->restore();
        $employe->update(['is_archived' => false, 'updated_by' => Auth::id()]);

        return response()->json(['message' => 'Employé restauré avec succès', 'data' => $employe->load(['departement', 'poste', 'service'])]);
    }

    public function export(Request $request)
    {
        $query = $this->filteredQuery($request);
        $employes = $query->orderBy('nom')->get();

        return Excel::download(new EmployesExport($employes), 'employes-rh.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new EmployesImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => 'Import terminé avec succès',
            'data' => [
                'created' => $import->getCreatedCount(),
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
                'errors' => $import->getErrors(),
            ],
        ]);
    }

    public function history(Employe $employe): JsonResponse
    {
        $activities = Activity::query()
            ->with(['causer:id,name,email'])
            ->where('subject_type', Employe::class)
            ->where('subject_id', $employe->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $activities->through(fn ($activity) => [
                'id' => $activity->id,
                'event' => $activity->event,
                'description' => $activity->description,
                'properties' => $activity->properties,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                    'email' => $activity->causer->email,
                ] : null,
                'created_at' => $activity->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function byService(Request $request, string $service): JsonResponse
    {
        $request->merge(['service_id' => $service]);

        return $this->index($request);
    }

    public function dashboardHistory(Request $request): JsonResponse
    {
        $query = Activity::query()
            ->with(['causer:id,name,email'])
            ->where('subject_type', Employe::class)
            ->orderByDesc('created_at');

        if ($request->filled('event')) {
            $query->forEvent($request->input('event'));
        }

        return response()->json([
            'data' => $query->paginate((int) $request->input('per_page', 15)),
        ]);
    }

    protected function filteredQuery(Request $request)
    {
        $query = Employe::query()->with([
            'departement:id,nom,couleur',
            'poste:id,nom',
            'service:id,nom',
            'user:id,name,email',
            'pays:id,name',
            'province:id,name',
            'commune:id,name',
            'zone:id,name',
            'colline:id,name',
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        foreach (['departement_id', 'poste_id', 'service_id', 'statut', 'temps_travail', 'type_contrat'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return $query;
    }

    protected function validatePayload(Request $request, ?Employe $employe = null): array
    {
        $photoRules = $request->hasFile('photo_path')
            ? ['nullable', 'file', 'image', 'max:2048']
            : ['nullable', 'string', 'max:255'];

        $payload = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'departement_id' => ['nullable', 'exists:departements,id'],
            'poste_id' => ['nullable', 'exists:postes,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'superieur_id' => ['nullable', 'exists:employes,id'],
            'pays_id' => ['nullable', 'exists:pays,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'commune_id' => ['nullable', 'exists:communes,id'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'colline_id' => ['nullable', 'exists:collines,id'],
            'matricule' => [
                $employe ? 'sometimes' : 'nullable',
                'string',
                'max:50',
                Rule::unique('employes', 'matricule')->ignore($employe?->id),
            ],
            'photo_path' => $photoRules,
            'nom' => [$employe ? 'sometimes' : 'required', 'string', 'max:255'],
            'prenom' => [$employe ? 'sometimes' : 'required', 'string', 'max:255'],
            'sexe' => ['nullable', 'string', 'max:10'],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'nationalite' => ['nullable', 'string', 'max:255'],
            'etat_civil' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employes', 'email')->ignore($employe?->id)],
            'adresse' => ['nullable', 'string'],
            'ville' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'pays' => ['nullable', 'string', 'max:255'],
            'date_embauche' => ['nullable', 'date'],
            'type_contrat' => ['nullable', 'string', 'max:255'],
            'date_debut_contrat' => ['nullable', 'date'],
            'date_fin_contrat' => ['nullable', 'date'],
            'salaire_base' => ['nullable', 'numeric', 'min:0'],
            'mode_paiement' => ['nullable', 'string', 'max:255'],
            'numero_cnss' => ['nullable', 'string', 'max:255'],
            'numero_nif' => ['nullable', 'string', 'max:255'],
            'lieu_travail' => ['nullable', 'string', 'max:255'],
            'temps_travail' => ['nullable', 'string', 'max:50'],
            'statut' => ['nullable', 'string', 'max:50'],
            'is_archived' => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('photo_path')) {
            $payload['photo_path'] = $this->storePhoto($request->file('photo_path'));
        }

        return array_filter($payload, fn ($value) => ! is_null($value));
    }

    protected function storePhoto(UploadedFile $file): string
    {
        $disk = Storage::disk('public');
        $directory = 'hr/employees/photos';
        $disk->makeDirectory($directory);

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid()->toString() . '.' . $extension;

        return $disk->putFileAs($directory, $file, $filename);
    }

    protected function generateMatricule(string $nom, string $prenom): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nom . $prenom), 0, 6));
        $suffix = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return 'EMP-' . $base . '-' . $suffix;
    }
}
