<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;

class ApplyIncludesToQuery implements HandlesRequestQueries
{
    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $requestQueryObject
     * @param  \Closure  $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $requestQueryObject, Closure $next)
    {
        if (empty($requestQueryObject->includes())) {
            return $next($requestQueryObject);
        }

        $this->applyIncludes(
            $requestQueryObject->query,
            $requestQueryObject->userAllowedIncludes()
        );

        return $next($requestQueryObject);
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
        $eagerLoadedRelationships = $query->getEagerLoads();
        $includes = array_filter($includes, fn ($include) => ! in_array($include, $eagerLoadedRelationships));

        if (! empty($includes)) {
            $query->with($includes);
        }

        return $query;
    }
}
