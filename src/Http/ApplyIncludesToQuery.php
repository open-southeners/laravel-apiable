<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;

class ApplyIncludesToQuery implements HandlesRequestQueries
{
    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \Closure(\OpenSoutheners\LaravelApiable\Http\RequestQueryObject): \Illuminate\Database\Eloquent\Builder  $next
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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyIncludes(Builder $query, array $includes)
    {
        $eagerLoadedRelationships = $query->getEagerLoads();

        foreach ($includes as $include) {
            match (true) {
                Str::endsWith($include, '_count') => $query->withCount(str_replace('_count', '', $include)),
                ! in_array($include, $eagerLoadedRelationships) => $query->with($include),
                default => null
            };
        }

        return $query;
    }
}
