<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

use Closure;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;

interface HandlesRequestQueries
{
    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $requestQueryObject
     * @param  \Closure  $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $requestQueryObject, Closure $next);
}
