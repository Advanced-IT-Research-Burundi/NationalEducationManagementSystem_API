<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Audit Log Controller
 *
 * Consults activity logs from Spatie Activity Log.
 * Filters: by user (causer), by entity (subject), by period, by action (event).
 */
class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Activity::query()
            ->with(['causer:id,name,email', 'subject'])
            ->orderByDesc('created_at');

        // Filter by user (causer_id)
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id)
                ->where('causer_type', 'App\Models\User');
        }

        // Filter by entity (subject_type) - e.g. App\Models\Eleve, App\Models\School
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // Filter by log name (schools, eleves, users, inscriptions_eleves)
        if ($request->filled('log_name')) {
            $query->inLog($request->log_name);
        }

        // Filter by event (created, updated, deleted)
        if ($request->filled('event')) {
            $query->forEvent($request->event);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $activities = $query->paginate($perPage);

        $items = $activities->getCollection()->map(fn ($activity) => [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'causer' => $activity->causer ? [
                'id' => $activity->causer->id,
                'name' => $activity->causer->name ?? null,
                'email' => $activity->causer->email ?? null,
            ] : null,
            'properties' => $activity->properties,
            'created_at' => $activity->created_at?->toIso8601String(),
        ])->values()->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Human readable labels for logged subject types.
     *
     * @var array<string, string>
     */
    protected const SUBJECT_TYPE_LABELS = [
        'School' => 'École',
        'Eleve' => 'Élève',
        'User' => 'Utilisateur',
        'Employe' => 'Employé',
        'Poste' => 'Poste',
        'Departement' => 'Département',
        'Formation' => 'Formation',
        'InscriptionEleve' => 'Inscription',
    ];

    /**
     * Human readable labels for logged events.
     *
     * @var array<string, string>
     */
    protected const EVENT_LABELS = [
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
        'restored' => 'Restauration',
    ];

    /**
     * Latest activities for the national dashboard, reserved to super admins and national admins.
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->hasRole(Role::ADMIN_NATIONAL)) {
            abort(403, 'Accès réservé aux administrateurs nationaux.');
        }

        $limit = min(max((int) $request->get('limit', 5), 1), 20);

        $items = Activity::query()
            ->with(['causer', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'event' => $activity->event,
                'event_label' => self::EVENT_LABELS[$activity->event] ?? ($activity->event ?: 'Action'),
                'subject_type_label' => $this->subjectTypeLabel($activity->subject_type),
                'subject_label' => $this->subjectLabel($activity),
                'causer_name' => $this->causerName($activity->causer),
                'created_at' => $activity->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return response()->json(['data' => $items]);
    }

    protected function subjectTypeLabel(?string $subjectType): string
    {
        if (! $subjectType) {
            return 'Élément';
        }

        $baseName = class_basename($subjectType);

        return self::SUBJECT_TYPE_LABELS[$baseName] ?? $baseName;
    }

    protected function subjectLabel(Activity $activity): string
    {
        $subject = $activity->subject;
        $attributes = $subject instanceof Model
            ? $subject->getAttributes()
            : (array) ($activity->properties['old'] ?? $activity->properties['attributes'] ?? []);

        $fullName = trim(($attributes['prenom'] ?? '').' '.($attributes['nom'] ?? ''));

        $label = $attributes['nom_complet'] ?? $attributes['name'] ?? ($fullName !== '' ? $fullName : null)
            ?? $attributes['libelle'] ?? $attributes['titre'] ?? $attributes['code'] ?? null;

        if ($label) {
            return (string) $label;
        }

        return $activity->subject_id ? '#'.$activity->subject_id : '—';
    }

    protected function causerName(?Model $causer): string
    {
        if (! $causer) {
            return 'Système';
        }

        $fullName = trim(($causer->prenom ?? '').' '.($causer->nom ?? ''));

        return $causer->name ?: ($fullName !== '' ? $fullName : ($causer->email ?? 'Utilisateur #'.$causer->getKey()));
    }

    public function show(string $id): JsonResponse
    {
        $activity = Activity::with(['causer:id,name,email', 'subject'])->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'event' => $activity->event,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'subject' => $activity->subject,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name ?? null,
                    'email' => $activity->causer->email ?? null,
                ] : null,
                'properties' => $activity->properties,
                'created_at' => $activity->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function byUser(string $user): JsonResponse
    {
        $request = request();
        $request->merge(['user_id' => $user]);

        return $this->index($request);
    }

    public function byAction(string $action): JsonResponse
    {
        $request = request();
        $request->merge(['event' => $action]);

        return $this->index($request);
    }

    public function export(Request $request): JsonResponse
    {
        $query = Activity::query()
            ->with(['causer:id,name,email'])
            ->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id)->where('causer_type', 'App\Models\User');
        }
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }
        if ($request->filled('log_name')) {
            $query->inLog($request->log_name);
        }
        if ($request->filled('event')) {
            $query->forEvent($request->event);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $limit = min((int) $request->get('limit', 1000), 5000);
        $activities = $query->limit($limit)->get();

        $data = $activities->map(fn ($a) => [
            'id' => $a->id,
            'log_name' => $a->log_name,
            'description' => $a->description,
            'event' => $a->event,
            'subject_type' => class_basename($a->subject_type ?? ''),
            'subject_id' => $a->subject_id,
            'causer_name' => $a->causer?->name,
            'causer_email' => $a->causer?->email,
            'created_at' => $a->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'exported_at' => now()->toIso8601String(),
        ]);
    }

    public function userActivity(string $user): JsonResponse
    {
        return $this->byUser($user);
    }
}
