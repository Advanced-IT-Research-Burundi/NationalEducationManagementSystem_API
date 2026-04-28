<?php

namespace App\Traits;

use App\Scopes\AcademicYearScope;

trait HasAcademicYearScope
{
    public static function bootHasAcademicYearScope(): void
    {
        static::addGlobalScope(new AcademicYearScope);
    }

    /**
     * Return the column name for direct academic year filtering,
     * or null if this model uses indirect filtering.
     */
    abstract protected static function academicYearColumn(): ?string;

    /**
     * Return the relationship path for indirect academic year filtering,
     * or null if this model uses direct filtering.
     * Supports dot notation for nested relations (e.g. 'session.examen').
     */
    abstract protected static function academicYearRelation(): ?string;
}
