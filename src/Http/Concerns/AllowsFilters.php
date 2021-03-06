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
        return array_filter($this->request->get('filter', []));
    }

    /**
     * Allow filter by attribute and pattern of value(s).
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedFilter|string  $attribute
     * @param  array<string>|string  $value
     * @return $this
     */
    public function allowFilter($attribute, $value = '*')
    {
        $this->allowedFilters = array_merge_recursive(
            $this->allowedFilters,
            $attribute instanceof AllowedFilter
                ? $attribute->toArray()
                : AllowedFilter::make($attribute, $value)->toArray()
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
