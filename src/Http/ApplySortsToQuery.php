<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;

class ApplySortsToQuery implements HandlesRequestQueries
{
    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $request
     * @param \Closure(\OpenSoutheners\LaravelApiable\Http\RequestQueryObject): \Illuminate\Database\Eloquent\Builder $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $request, Closure $next)
    {
        $userSorts = $request->userAllowedSorts() ?: $request->getDefaultSorts();

        $this->applySorts($request->query, $userSorts);

        return $next($request);
    }

    /**
     * Apply array of sorts to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $sorts
     */
    protected function applySorts(Builder $query, array $sorts): void
    {
        foreach ($sorts as $attribute => $direction) {
            $query->orderBy($this->getQualifiedAttribute($query, $attribute, $direction), $direction);
        }
    }

    /**
     * Get attribute adding a join when sorting by relationship or a column sort.
     *  
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $attribute
     * @param string $direction
     * @return string|\Closure|\Illuminate\Database\Eloquent\Builder
     */
    protected function getQualifiedAttribute(Builder $query, string $attribute, string $direction)
    {
        $queryModel = $query->getModel();

        if (! str_contains($attribute, '.')) {
            return $queryModel->qualifyColumn($attribute);
        }

        [$relationship, $column] = explode('.', $attribute);

        if (! method_exists($queryModel, $relationship)) {
            return $queryModel->qualifyColumn($column);
        }

        /** @var \Illuminate\Database\Eloquent\Relations\HasOneOrMany|\Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Relations\BelongsToMany $relationshipMethod */
        $relationshipMethod = call_user_func([$queryModel, $relationship]);
        $relationshipModel = $relationshipMethod->getRelated();

        if (is_a($relationshipMethod, BelongsToMany::class)) {
            return $relationshipModel->newQuery()
                ->select($column)
                ->join($relationshipMethod->getTable(), $relationshipMethod->getRelatedPivotKeyName(), $relationshipModel->getQualifiedKeyName())
                ->whereColumn($relationshipMethod->getQualifiedForeignPivotKeyName(), $queryModel->getQualifiedKeyName())
                ->orderBy($column, $direction)
                ->limit(1);
        }

        $relationshipTable = $relationshipModel->getTable();
        $joinAsRelationshipTable = $relationshipTable;

        if ($relationshipTable === $queryModel->getTable()) {
            $joinAsRelationshipTable = "{$relationship}_{$relationshipTable}";
        }

        $joinName = $relationshipTable . ($joinAsRelationshipTable !== $relationshipTable ? " as {$joinAsRelationshipTable}" : '');

        $query->select($queryModel->qualifyColumn('*'));

        $query->when(
            ! $query->hasJoin($joinName),
            fn (Builder $query) => $query->join(
                $joinName,
                "{$joinAsRelationshipTable}.{$relationshipMethod->getOwnerKeyName()}",
                '=',
                $relationshipMethod->getQualifiedForeignKeyName()
            )
        );

        return "{$joinAsRelationshipTable}.{$column}";
    }
}
