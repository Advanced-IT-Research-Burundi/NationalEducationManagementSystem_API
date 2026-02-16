<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

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
        'role_id',
        'statut',
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
            'password' => 'hashed',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relationships
    // Note: role() relationship might key conflict with Spatie's roles() if not careful,
    // but Spatie uses permissions based system.
    // Ideally we should remove the 'role_id' column and 'role()' relation if we are fully switching to Spatie,
    // but the task was to update the user table, not necessarily refactor everything immediately unless requested.
    // However, keeping it simple for now.

    public function role()
    {
        return $this->belongsTo(Role::class);
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
        return $this->belongsTo(Ecole::class);
    }

    // Helper to check permission (Manual implementation if needed, but Spatie provides can())
    public function hasPermission($permissionSlug)
    {
        return $this->can($permissionSlug);
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
    public function canAccessSchool(School $school): bool
    {
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
}
