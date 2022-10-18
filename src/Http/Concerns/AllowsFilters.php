<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Support\Apiable;

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
     * @param  array<string>|string|int  $operator
     * @param  array<string>|string  $values
     * @return $this
     */
    public function allowFilter($attribute, $operator = ['*'], $values = ['*'])
    {
        if ($values === ['*'] && (is_array($operator) || is_string($operator))) {
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
     * Get user requested filters filtered by allowed ones.
     *
     * @return array
     */
    public function userAllowedFilters()
    {
        $defaultFilterOperator = Apiable::config('requests.filters.default_operator');
        $throwOnValidationError = fn ($key) => throw new Exception(sprintf('"%s" is not filterable or contains invalid values', $key));

        return $this->validator($this->filters())
            ->givingRules($this->allowedFilters)
            ->whenPatternMatches($throwOnValidationError)
            ->when(function ($key, $modifiers, $values, $rules) use ($defaultFilterOperator) {
                $allowedOperators = (array) $rules['operator'] ?? $defaultFilterOperator;

                return ! empty(array_intersect($modifiers, $allowedOperators));
            }, $throwOnValidationError)
            ->validate();
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
