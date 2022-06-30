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

    /**
     * Applies where/orWhere to all filtered values.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $attribute
     * @param  array  $filterValues
     * @return void
     */
    protected function applyArrayOfFiltersToQuery(Builder $query, string $attribute, array $filterValues)
    {
        for ($i = 0; $i < count($filterValues); $i++) {
            $filterValue = $filterValues[$i];
            $filterOperator = array_keys($this->allowed[$attribute])[0];

            if ($filterOperator === 'like') {
                $filterValue = "%${filterValue}%";
            }

            $query->where($attribute, $filterOperator, $filterValue, $i === 0 ? 'and' : 'or');
        }
    }
}
