<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;

interface HandlesRequestQueries
{
    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  Closure(RequestQueryObject): Builder  $next
     * @return Builder
     */
    public function from(RequestQueryObject $request, Closure $next);
}
