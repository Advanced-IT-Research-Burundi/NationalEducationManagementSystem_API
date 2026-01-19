<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
        'pays_id',
        'ministere_id',
        'province_id',
        'commune_id',
        'zone_id',
        'colline_id',
        'school_id',
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

    // Relationships
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

    // Helper to check permission
    public function hasPermission($permissionSlug)
    {
        return $this->role && $this->role->permissions->contains('slug', $permissionSlug);
    }
}
