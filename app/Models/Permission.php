<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    protected $fillable = ['name', 'guard_name', 'description', 'group_name', 'is_system', 'sort_order'];

    protected $casts = [
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['label', 'group_label'];

    public function getLabelAttribute(): string
    {
        return Str::of($this->name)
            ->replace(['.', '_'], ' ')
            ->headline()
            ->toString();
    }

    public function getGroupLabelAttribute(): string
    {
        return Str::of($this->group_name ?: $this->inferGroupName())
            ->replace(['.', '_'], ' ')
            ->headline()
            ->toString();
    }

    public function isSystemPermission(): bool
    {
        return (bool) $this->is_system;
    }

    protected function inferGroupName(): string
    {
        if (Str::contains($this->name, '.')) {
            return Str::before($this->name, '.');
        }

        if (Str::startsWith($this->name, 'access_')) {
            return 'access';
        }

        if (Str::startsWith($this->name, 'manage_')) {
            return 'management';
        }

        if (Str::startsWith($this->name, 'view_')) {
            return 'consultation';
        }

        $segments = explode('_', $this->name);

        if (count($segments) > 1) {
            array_shift($segments);

            return implode('_', $segments);
        }

        return 'general';
    }
}
