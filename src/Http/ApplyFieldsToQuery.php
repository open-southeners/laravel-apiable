<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

class ApplyFieldsToQuery implements HandlesRequestQueries
{
    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  Closure(RequestQueryObject): Builder  $next
     * @return Builder
     */
    public function from(RequestQueryObject $request, Closure $next)
    {
        if (count($request->query->toBase()->columns ?: []) === 0) {
            $request->query->select($request->query->qualifyColumn('*'));
        }

        if (count($request->fields()) === 0 || count($request->getAllowedFields()) === 0) {
            return $next($request);
        }

        $this->applyFields($request->query, $request->userAllowedFields());

        return $next($request);
    }

    /**
     * Apply array of fields to the query.
     *
     * @return Builder
     */
    protected function applyFields(Builder $query, array $fields)
    {
        /** @var JsonApiable|Model $mainQueryModel */
        $mainQueryModel = $query->getModel();
        $mainQueryResourceType = Apiable::getResourceType($mainQueryModel);
        $queryEagerLoaded = $query->getEagerLoads();

        // TODO: Move this to some class methods
        foreach ($fields as $type => $columns) {
            if ($mainQueryResourceType === $type) {
                $query->select($mainQueryModel->qualifyColumns(
                    $this->columnsForIncludes($mainQueryModel, $columns, array_keys($queryEagerLoaded))
                ));

                continue;
            }

            foreach ($queryEagerLoaded as $path => $constraints) {
                if (str_contains($path, '.')) {
                    continue;
                }

                $relation = $this->relationFor($mainQueryModel, $path);

                if (! $relation || Apiable::getResourceType($relation->getRelated()) !== $type) {
                    continue;
                }

                $query->with($path, function (Relation $relatedQuery) use ($columns, $constraints, $relation) {
                    $relatedModel = $relatedQuery->getRelated();
                    $relatedColumns = $this->columnsForIncludes(
                        $relatedModel,
                        $columns,
                        array_keys($relatedQuery->getEagerLoads())
                    );

                    if ($relation instanceof HasOneOrMany) {
                        $relatedColumns[] = $relation->getForeignKeyName();
                    }

                    $relatedQuery->select($relatedModel->qualifyColumns(array_unique($relatedColumns)));
                    $constraints($relatedQuery);
                });
            }
        }

        return $query;
    }

    /**
     * Keep the keys Eloquent needs to match requested includes after a sparse select.
     *
     * @param  array<string>  $columns
     * @param  array<string>  $includePaths
     * @return array<string>
     */
    protected function columnsForIncludes(Model $model, array $columns, array $includePaths): array
    {
        $columns[] = $model->getKeyName();

        foreach ($includePaths as $path) {
            $relation = $this->relationFor($model, explode('.', $path)[0]);

            if ($relation instanceof BelongsTo) {
                $columns[] = $relation->getForeignKeyName();
            }

            if ($relation instanceof MorphTo) {
                $columns[] = $relation->getMorphType();
            }
        }

        return array_values(array_unique($columns));
    }

    protected function relationFor(Model $model, string $name): ?Relation
    {
        if (! method_exists($model, $name)) {
            return null;
        }

        $relation = Relation::noConstraints(fn () => $model->{$name}());

        return $relation instanceof Relation ? $relation : null;
    }
}
