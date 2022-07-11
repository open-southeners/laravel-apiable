<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;

class ApplyIncludesToQuery implements HandlesRequestQueries
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
        $includes = $requestQueryObject->includes();

        if (empty($includes)) {
            return $next($requestQueryObject);
        }

        $this->allowed = $requestQueryObject->getAllowedIncludes();

        $this->applyIncludes(
            $requestQueryObject->query,
            $this->getUserIncludes($includes, $requestQueryObject->query)
        );

        return $next($requestQueryObject);
    }

    /**
     * Get user allowed includes.
     *
     * @param  array  $includes
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return array
     */
    protected function getUserIncludes(array $includes, $query)
    {
        $queryEagerLoadedRelations = array_keys($query->getEagerLoads());

        return array_filter($includes, function ($include) use ($queryEagerLoadedRelations) {
            return in_array($include, $this->allowed) && ! in_array($include, $queryEagerLoadedRelations);
        });
    }

    /**
     * Apply array of includes to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $includes
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyIncludes(Builder $query, array $includes)
    {
        if (! empty($includes)) {
            $query->with($includes);
        }

        return $query;
    }
}
