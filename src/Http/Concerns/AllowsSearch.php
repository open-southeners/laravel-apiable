<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;
use OpenSoutheners\LaravelApiable\Http\AllowedSearchFilter;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
trait AllowsSearch
{
    /**
     * @var bool
     */
    protected $allowedSearch = false;

    /**
     * @var array
     */
    protected $allowedSearchFilters = [];

    /**
     * Get user search query from request.
     *
     * @return string
     */
    public function searchQuery()
    {
        return head(array_filter(
            $this->queryParameters()->get('q', $this->queryParameters()->get('search', [])),
            fn ($item) => is_string($item)
        ));
    }

    /**
     * Get user search query filters from request.
     *
     * @return string[]
     */
    public function searchFilters()
    {
        return array_reduce(array_filter(
            $this->queryParameters()->get('q', $this->queryParameters()->get('search', [])),
            fn ($item) => is_array($item) && head(array_keys($item)) === 'filter'
        ), function ($result, $item) {
            $filterFromItem = head(array_values($item));

            $result[head(array_keys($filterFromItem))] = [
                'values' => head(array_values($filterFromItem)),
            ];

            return $result;
        });
    }

    /**
     * Allow fulltext search to be performed.
     *
     * @return $this
     */
    public function allowSearch(bool $value = true)
    {
        $this->allowedSearch = $value;

        return $this;
    }

    /**
     * Allow filter search by attribute and pattern of value(s).
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedSearchFilter|string  $attribute
     * @param  array<string>|string  $values
     * @return $this
     */
    public function allowSearchFilter($attribute, $values = ['*'])
    {
        $this->allowedSearchFilters = array_merge_recursive(
            $this->allowedSearchFilters,
            $attribute instanceof AllowedSearchFilter
                ? $attribute->toArray()
                : (new AllowedSearchFilter($attribute, $values))->toArray()
        );

        return $this;
    }

    /**
     * Check if fulltext search is allowed.
     *
     * @return bool
     */
    public function isSearchAllowed()
    {
        return $this->allowedSearch;
    }

    /**
     * Get user requested search filters filtered by allowed ones.
     *
     * @return array
     */
    public function userAllowedSearchFilters()
    {
        $searchFilters = $this->searchFilters();

        if (empty($searchFilters)) {
            return [];
        }

        return $this->validator($this->searchFilters())
            ->givingRules($this->allowedSearchFilters)
            ->whenPatternMatches(fn ($key) => throw new Exception(sprintf('"%s" is not filterable on search or contains invalid values', $key)))
            ->validate();
    }

    /**
     * Get list of allowed search filters.
     *
     * @return array<string, string>
     */
    public function getAllowedSearchFilters()
    {
        return $this->allowedSearchFilters;
    }
}
