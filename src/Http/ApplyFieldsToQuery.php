<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;

class ApplyFieldsToQuery implements HandlesRequestQueries
{
    /**
     * @var array
     */
    protected $allowed = [];

    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $requestQueryObject
     * @param  \Closure  $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $requestQueryObject, Closure $next)
    {
        $fields = $requestQueryObject->fields();

        $this->allowed = $requestQueryObject->getAllowedFields();

        if (empty($fields) || empty($this->allowed)) {
            return $next($requestQueryObject);
        }

        $this->applyFields(
            $requestQueryObject->query,
            $this->getUserFields($fields)
        );

        return $next($requestQueryObject);
    }

    protected function getUserFields(array $fields)
    {
        $allowedUserFieldsArr = [];

        foreach ($fields as $type => $columns) {
            if (! isset($this->allowed[$type])) {
                continue;
            }

            if ($this->allowed[$type] === '*') {
                $allowedUserFieldsArr[$type] = $columns;

                continue;
            }

            $allowedUserFieldsArr[$type] = array_intersect($columns, $this->allowed[$type]);
        }

        return array_filter($allowedUserFieldsArr);
    }

    /**
     * Apply array of fields to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $fields
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFields(Builder $query, array $fields)
    {
        /** @var \OpenSoutheners\LaravelApiable\Contracts\JsonApiable */
        $mainQueryModel = $query->getModel();
        $queryEagerLoaded = $query->getEagerLoads();

        // TODO: Move this to some class methods
        foreach ($fields as $type => $columns) {
            if ($mainQueryModel->jsonApiableOptions()->resourceType === $type) {
                if (! in_array($mainQueryModel->getKeyName(), $columns)) {
                    $columns[] = $mainQueryModel->getKeyName();
                }

                $query->select($columns);

                continue;
            }

            $query->when(in_array($type, $queryEagerLoaded), fn (Builder $query) => $query
                ->with($type, function (Builder $query) use ($queryEagerLoaded, $type, $columns) {
                    $relatedModel = $query->getModel();

                    if (! in_array($relatedModel->getKeyName(), $columns)) {
                        $columns[] = $relatedModel->getKeyName();
                    }

                    $queryEagerLoaded[$type]($query->select($columns));
                })
            );
        }

        return $query;
    }
}
