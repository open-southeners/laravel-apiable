<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use OpenSoutheners\LaravelApiable\Http\AllowedFilter;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
trait AllowsFilters
{
    /**
     * @var array<string, string>
     */
    protected $allowedFilters = [];

    /**
     * Get user filters from request.
     *
     * @return array
     */
    public function filters()
    {
        return $this->request->get('filter', []);
    }

    /**
     * Allow filter by attribute and pattern of value(s).
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedFilter|string  $attribute
     * @param  array<string>|string  $operator
     * @param  array<string>|string  $values
     * @return $this
     */
    public function allowFilter($attribute, $operator = ['*'], $values = ['*'])
    {
        if (is_array($operator) || (is_string($operator) && ! in_array($operator, AllowedFilter::OPERATORS))) {
            $values = $operator;

            $operator = null;
        }

        $this->allowedFilters = array_merge_recursive(
            $this->allowedFilters,
            $attribute instanceof AllowedFilter
                ? $attribute->toArray()
                : (new AllowedFilter($attribute, $operator, $values))->toArray()
        );

        return $this;
    }

    /**
     * Allow filter by scope and pattern of value(s).
     *
     * @param  string  $attribute
     * @param  array<string>|string  $value
     * @return $this
     */
    public function allowScopedFilter($attribute, $value = '*')
    {
        $this->allowedFilters = array_merge_recursive(
            $this->allowedFilters,
            AllowedFilter::scoped($attribute, $value)->toArray()
        );

        return $this;
    }

    /**
     * Get list of allowed filters.
     *
     * @return array<string, string>
     */
    public function getAllowedFilters()
    {
        return $this->allowedFilters;
    }
}
