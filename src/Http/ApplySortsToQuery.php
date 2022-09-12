<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;

class ApplySortsToQuery implements HandlesRequestQueries
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
        $sorts = $requestQueryObject->sorts();

        if (empty($sorts)) {
            return $next($requestQueryObject);
        }

        $this->allowed = $requestQueryObject->getAllowedSorts();

        $this->applySorts(
            $requestQueryObject->query,
            $this->getUserSorts($sorts)
        );

        return $next($requestQueryObject);
    }

    protected function getUserSorts(array $sorts)
    {
        return array_filter($sorts, function ($direction, $attribute) {
            if (! isset($this->allowed[$attribute])) {
                return false;
            }

            if ($this->allowed[$attribute] === '*') {
                return true;
            }

            return $this->allowed[$attribute] === $direction;
        }, ARRAY_FILTER_USE_BOTH);
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
