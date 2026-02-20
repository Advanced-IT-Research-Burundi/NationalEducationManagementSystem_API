<?php

namespace App\Models;

use App\Traits\HasDataScope;
use App\Traits\HasMatricule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class School extends Model
{
    use HasDataScope, HasFactory, SoftDeletes, LogsActivity, HasMatricule;

    protected $table = 'schools';

    // Workflow status constants
    const STATUS_BROUILLON = 'BROUILLON';

    const STATUS_EN_ATTENTE_VALIDATION = 'EN_ATTENTE_VALIDATION';

    const STATUS_ACTIVE = 'ACTIVE';

    const STATUS_INACTIVE = 'INACTIVE';

    // Type constants
    const TYPE_PUBLIQUE = 'PUBLIQUE';

    const TYPE_PRIVEE = 'PRIVEE';

    const TYPE_ECC = 'ECC';

    const TYPE_AUTRE = 'AUTRE';

    // Niveau constants
    const NIVEAU_FONDAMENTAL = 'FONDAMENTAL';

    const NIVEAU_POST_FONDAMENTAL = 'POST_FONDAMENTAL';

    const NIVEAU_SECONDAIRE = 'SECONDAIRE';

    const NIVEAU_SUPERIEUR = 'SUPERIEUR';

    protected $fillable = [
        'name',
        'code_ecole',
        'type_ecole',
        'niveau',
        'statut',
        'latitude',
        'longitude',
        'colline_id',
        'zone_id',
        'commune_id',
        'province_id',
        'ministere_id',
        'pays_id',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $appends = ['statut_label'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('schools')
            ->dontSubmitEmptyLogs();
    }

    /**
     * Workflow Helper Methods
     */
    public function canSubmit(): bool
    {
        return $this->statut === self::STATUS_BROUILLON
            && $this->hasRequiredFieldsForSubmission();
    }

    public function canValidate(): bool
    {
        return $this->statut === self::STATUS_EN_ATTENTE_VALIDATION
            && $this->hasRequiredFieldsForValidation();
    }

    public function canDeactivate(): bool
    {
        return $this->statut === self::STATUS_ACTIVE;
    }

    public function hasRequiredFieldsForSubmission(): bool
    {
        return ! empty($this->name)
            && ! empty($this->code_ecole)
            && ! empty($this->type_ecole)
            && ! empty($this->niveau)
            && ! empty($this->colline_id);
    }

    public function hasRequiredFieldsForValidation(): bool
    {
        return $this->hasRequiredFieldsForSubmission()
            && ! is_null($this->latitude)
            && ! is_null($this->longitude);
    }

    /**
     * Query Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('statut', self::STATUS_BROUILLON);
    }

    public function scopePending($query)
    {
        return $query->where('statut', self::STATUS_EN_ATTENTE_VALIDATION);
    }

    public function scopeActive($query)
    {
        return $query->where('statut', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('statut', self::STATUS_INACTIVE);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type_ecole', $type);
    }

    public function scopeByNiveau($query, $niveau)
    {
        return $query->where('niveau', $niveau);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('code_ecole', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Accessors
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUS_BROUILLON => 'Brouillon',
            self::STATUS_EN_ATTENTE_VALIDATION => 'En attente de validation',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            default => 'Inconnu',
        };
    }

    /**
     * Relationships
     */
    public function colline()
    {
        return $this->belongsTo(Colline::class);
    }

    // Parent relationships
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function ministere()
    {
        return $this->belongsTo(Ministere::class);
    }

    public function pays()
    {
        return $this->belongsTo(Pays::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Academic relationships
    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function enseignants()
    {
        return $this->hasMany(Enseignant::class);
    }

    public function eleves()
    {
        return $this->hasMany(Eleve::class);
    }

}
