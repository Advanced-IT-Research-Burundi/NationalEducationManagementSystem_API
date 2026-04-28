<?php

namespace App\Scopes;

use App\Services\AcademicYearService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AcademicYearScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $yearId = AcademicYearService::currentId();

        if ($yearId === null) {
            return;
        }

        $column = $model::academicYearColumn();
        $relation = $model::academicYearRelation();

        if ($column !== null) {
            $builder->where($model->qualifyColumn($column), $yearId);
        } elseif ($relation !== null) {
            $this->applyIndirectScope($builder, $relation, $yearId);
        }
    }

    /**
     * Filter through a relationship chain where the final model
     * has a direct annee_scolaire_id column.
     */
    protected function applyIndirectScope(Builder $builder, string $relation, int $yearId): void
    {
        $parts = explode('.', $relation);

        if (count($parts) === 1) {
            $builder->whereHas($relation, function (Builder $query) use ($yearId) {
                $query->withoutGlobalScope(self::class)
                    ->where('annee_scolaire_id', $yearId);
            });
        } else {
            $outerRelation = array_shift($parts);
            $innerPath = implode('.', $parts);

            $builder->whereHas($outerRelation, function (Builder $query) use ($innerPath, $yearId) {
                $query->withoutGlobalScope(self::class);
                $this->applyIndirectScope($query, $innerPath, $yearId);
            });
        }
    }
}
