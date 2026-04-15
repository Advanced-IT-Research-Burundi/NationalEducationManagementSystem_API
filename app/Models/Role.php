<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;

    public const SUPER_ADMIN = 'Super Administrateur';
    public const ADMIN_NATIONAL = 'Admin National';
    public const ADMIN_MINISTERE = 'Admin Ministère';
    public const DIRECTEUR_PROVINCIAL = 'Directeur Provincial';
    public const AGENT_COMMUNAL = 'Agent Communal';
    public const SUPERVISEUR_ZONE = 'Superviseur Zone';
    public const DIRECTEUR_ECOLE = 'Directeur École';
    public const ENSEIGNANT = 'Enseignant';
    public const PERSONNEL_ADMINISTRATIF = 'Personnel Administratif';

    public const SYSTEM_ROLE_NAMES = [
        self::SUPER_ADMIN,
        self::ADMIN_NATIONAL,
        self::ADMIN_MINISTERE,
        self::DIRECTEUR_PROVINCIAL,
        self::AGENT_COMMUNAL,
        self::SUPERVISEUR_ZONE,
        self::DIRECTEUR_ECOLE,
        self::ENSEIGNANT,
        self::PERSONNEL_ADMINISTRATIF,
    ];

    protected $fillable = ['name', 'guard_name', 'description', 'is_system', 'sort_order'];

    protected $casts = [
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['slug', 'is_editable'];

    // Explicitly define relationship to User to avoid dynamic guard lookup issues with withCount
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            'role_id',
            'model_id'
        );
    }

    public function getSlugAttribute(): string
    {
        return Str::of($this->name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    public function getIsEditableAttribute(): bool
    {
        return ! $this->isSystemRole();
    }

    public function isSystemRole(): bool
    {
        return (bool) $this->is_system || in_array($this->name, self::SYSTEM_ROLE_NAMES, true);
    }

    public function isSuperAdminRole(): bool
    {
        return $this->name === self::SUPER_ADMIN;
    }
}
