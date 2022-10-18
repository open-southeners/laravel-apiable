<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;

class ApplySortsToQuery implements HandlesRequestQueries
{
    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $request
     * @param  \Closure  $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $request, Closure $next)
    {
        if (empty($request->sorts())) {
            return $next($request);
        }

        $this->applySorts(
            $request->query,
            $request->userAllowedSorts()
        );

        return $next($request);
    }

    /**
     * Apply array of sorts to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $sorts
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySorts(Builder $query, array $sorts)
    {
        foreach ($sorts as $attribute => $direction) {
            $query->orderBy($attribute, $direction);
        }

        return $query;
    }
}
