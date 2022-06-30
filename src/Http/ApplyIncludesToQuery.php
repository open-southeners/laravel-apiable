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
            $this->getUserIncludes($includes)
        );

        return $next($requestQueryObject);
    }

    protected function getUserIncludes(array $includes)
    {
        return array_filter($includes, function ($include) {
            return in_array($include, $this->allowed);
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
        $query->with($includes);

        return $query;
    }
}
