<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
            $typeModel = Apiable::getModelFromResourceType($type);

            $matchedFn = match (true) {
                $mainQueryResourceType === $type => function () use ($query, $mainQueryModel, $columns) {
                    if (! in_array($mainQueryModel->getKeyName(), $columns)) {
                        $columns[] = $mainQueryModel->getQualifiedKeyName();
                    }

                    $query->select($mainQueryModel->qualifyColumns($columns));
                },
                in_array($typeModel, $queryEagerLoaded) => fn () => $query->with($type, function (Relation $query) use ($queryEagerLoaded, $type, $columns) {
                    $relatedModel = $query->getModel();

                    if (! in_array($relatedModel->getKeyName(), $columns)) {
                        $columns[] = $relatedModel->getKeyName();
                    }

                    $queryEagerLoaded[$type]($query->select($relatedModel->qualifyColumns($columns)));
                }),
                default => fn () => null,
            };

            $matchedFn();
        }

        return $query;
    }
}
