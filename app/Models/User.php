<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * Guard name for Spatie Permission.
     */
    protected string $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'statut',
        'is_super_admin',
        'admin_level',
        'admin_entity_id',
        'pays_id',
        'ministere_id',
        'province_id',
        'commune_id',
        'zone_id',
        'colline_id',
        'school_id',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['password', 'remember_token'])
            ->useLogName('users')
            ->dontSubmitEmptyLogs();
    }

    public function pays()
    {
        return $this->belongsTo(Pays::class);
    }

    public function ministere()
    {
        return $this->belongsTo(Ministere::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function colline()
    {
        return $this->belongsTo(Colline::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function ecole()
    {
        return $this->belongsTo(School::class);
    }

    // Helper to check permission (Manual implementation if needed, but Spatie provides can())
    public function hasPermission($permissionSlug)
    {
        return $this->can($permissionSlug);
    }

    public function hasAnyPermissionName(array $permissionNames): bool
    {
        foreach ($permissionNames as $permissionName) {
            if ($this->can($permissionName)) {
                return true;
            }
        }

        return false;
    }

    public function matchesRoleIdentifier(string $roleIdentifier): bool
    {
        $identifier = $this->normalizeRoleIdentifier($roleIdentifier);

        if ($identifier === '') {
            return false;
        }

        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        return $roles->contains(function (Role $role) use ($identifier) {
            $roleName = $this->normalizeRoleIdentifier($role->name);

            return $roleName === $identifier
                || $this->normalizeRoleIdentifier($role->slug) === $identifier;
        });
    }

    public function isSuperAdmin(): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->hasRole(Role::SUPER_ADMIN);
    }

    public function getRoleAttribute(): ?Role
    {
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        return $roles
            ->sortByDesc(fn (Role $role) => $role->sort_order)
            ->sortByDesc(fn (Role $role) => $role->isSuperAdminRole())
            ->first();
    }

    public function getPrimaryRole(): ?Role
    {
        return $this->role;
    }

    public function loadAuthorizationRelations(): self
    {
        return tap($this)->loadMissing([
            'roles.permissions',
            'permissions',
            'pays',
            'ministere',
            'province',
            'commune',
            'zone',
            'colline',
            'school',
        ]);
    }

    public function getAuthorizationSnapshot(): array
    {
        $this->loadAuthorizationRelations();

        /** @var \Illuminate\Support\Collection<int, Role> $roles */
        $roles = $this->roles->sortByDesc('sort_order')->values();
        $directPermissions = $this->permissions->keyBy('id');
        $allPermissions = $this->getAllPermissions()
            ->sortBy(fn (Permission $permission) => sprintf(
                '%s-%06d-%s',
                $permission->group_name ?? 'zzz',
                $permission->sort_order ?? 0,
                $permission->name
            ))
            ->values();

        return [
            'is_super_admin' => $this->isSuperAdmin(),
            'primary_role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'slug' => $this->role->slug,
                'description' => $this->role->description,
                'is_system' => $this->role->is_system,
            ] : null,
            'role_names' => $roles->pluck('name')->values()->all(),
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
            ])->all(),
            'permission_names' => $allPermissions->pluck('name')->values()->all(),
            'permissions' => $allPermissions->map(function (Permission $permission) use ($roles, $directPermissions) {
                $viaRoles = $roles
                    ->filter(fn (Role $role) => $role->permissions->contains('id', $permission->id))
                    ->pluck('name')
                    ->values()
                    ->all();

                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => $permission->label,
                    'description' => $permission->description,
                    'group_name' => $permission->group_name,
                    'group_label' => $permission->group_label,
                    'is_system' => $permission->is_system,
                    'source' => $directPermissions->has($permission->id) ? 'direct' : 'role',
                    'via_roles' => $viaRoles,
                ];
            })->all(),
        ];
    }

    /**
     * Get the administrative entity model this user belongs to.
     */
    public function getAdminEntity()
    {
        if (! $this->admin_level || ! $this->admin_entity_id) {
            return null;
        }

        return match ($this->admin_level) {
            'PAYS' => Pays::find($this->admin_entity_id),
            'MINISTERE' => Ministere::find($this->admin_entity_id),
            'PROVINCE' => Province::find($this->admin_entity_id),
            'COMMUNE' => Commune::find($this->admin_entity_id),
            'ZONE' => Zone::find($this->admin_entity_id),
            'ECOLE' => School::find($this->admin_entity_id),
            default => null,
        };
    }

    /**
     * Get the user who created this user.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if user is a school-level user.
     */
    public function isSchoolUser(): bool
    {
        return $this->admin_level === 'ECOLE';
    }

    /**
     * Check if user belongs to a specific school.
     */
    public function belongsToSchool(int $schoolId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->admin_level === 'ECOLE') {
            return $this->admin_entity_id === $schoolId || $this->school_id === $schoolId;
        }

        // Higher level users may have access to multiple schools
        return false;
    }

    /**
     * Check if user can access data for a specific school.
     * This considers the full hierarchy.
     */
    public function canAccessSchool(?School $school): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $school) {
            return $this->admin_level === 'PAYS';
        }

        return match ($this->admin_level) {
            'PAYS' => true,
            'MINISTERE' => $school->ministere_id === $this->admin_entity_id,
            'PROVINCE' => $school->province_id === $this->admin_entity_id,
            'COMMUNE' => $school->commune_id === $this->admin_entity_id,
            'ZONE' => $school->zone_id === $this->admin_entity_id,
            'ECOLE' => $school->id === $this->admin_entity_id,
            default => false,
        };
    }

    protected function normalizeRoleIdentifier(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;
        $normalized = trim($normalized, '_');

        return match ($normalized) {
            'officier_communal', 'responsable_communal' => 'agent_communal',
            'agent_administratif' => 'personnel_administratif',
            default => $normalized,
        };
    }
}
