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
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $request
     * @param \Closure(\OpenSoutheners\LaravelApiable\Http\RequestQueryObject): \Illuminate\Database\Eloquent\Builder $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $request, Closure $next)
    {
        if (empty($request->includes())) {
            return $next($request);
        }

        $this->applyIncludes(
            $request->query,
            $request->userAllowedIncludes()
        );

        return $next($request);
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
