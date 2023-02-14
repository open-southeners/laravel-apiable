<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
trait AllowsFilters
{
    /**
     * @var array<string, array>
     */
    protected array $allowedFilters = [];

    /**
     * Get user filters from request.
     */
    public function filters(): array
    {
        $queryStringArr = explode('&', $this->request->server('QUERY_STRING', ''));
        $filters = [];

        foreach ($queryStringArr as $param) {
            $filterQueryParam = HeaderUtils::parseQuery($param);

            if (! is_array(head($filterQueryParam))) {
                continue;
            }

            $filterQueryParamAttribute = head(array_keys($filterQueryParam));

            if ($filterQueryParamAttribute !== 'filter') {
                continue;
            }

            $filterQueryParam = head($filterQueryParam);
            $filterQueryParamAttribute = head(array_keys($filterQueryParam));
            $filterQueryParamValue = head(array_values($filterQueryParam));

            if (! isset($filters[$filterQueryParamAttribute])) {
                $filters[$filterQueryParamAttribute] = [$filterQueryParamValue];

                continue;
            }

            $filters[$filterQueryParamAttribute][] = $filterQueryParamValue;
        }

        return $filters;
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
     * @param  array<string>|string  $value
     */
    public function allowScopedFilter(string $attribute, array|string $value = '*'): self
    {
        $this->allowedFilters = array_merge_recursive(
            $this->allowedFilters,
            AllowedFilter::scoped($attribute, $value)->toArray()
        );

        return $this;
    }

    /**
     * Get user requested filters filtered by allowed ones.
     */
    public function userAllowedFilters(): array
    {
        $defaultFilterOperator = Apiable::config('requests.filters.default_operator');
        $throwOnValidationError = fn ($key) => throw new Exception(sprintf('"%s" is not filterable or contains invalid values', $key));

        return $this->validator($this->filters())
            ->givingRules($this->allowedFilters)
            ->whenPatternMatches($throwOnValidationError)
            ->when(function ($key, $modifiers, $values, $rules) use ($defaultFilterOperator): bool {
                $allowedOperators = (array) ($rules['operator'] ?? $defaultFilterOperator);

                return ! empty(array_intersect($modifiers, $allowedOperators));
            }, $throwOnValidationError)
            ->validate();
    }

    /**
     * Get list of allowed filters.
     *
     * @return array<string, array>
     */
    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }
}
