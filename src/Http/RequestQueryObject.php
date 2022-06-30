<?php

namespace OpenSoutheners\LaravelApiable\Http;

class RequestQueryObject
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    public $query;

    /**
     * @var array<string, string>
     */
    protected $allowedSorts = [];

    /**
     * @var array<string, string>
     */
    protected $allowedFilters = [];

    /**
     * @var array<string, string>
     */
    protected $allowedFields = [];

    /**
     * @var array<string>
     */
    protected $allowedIncludes = [];

    /**
     * Construct the request query object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function __construct($request, $query)
    {
        $this->request = $request;
        $this->query = $query;
    }

    /**
     * Get all includes from request.
     *
     * @return array
     */
    public function includes()
    {
        return array_filter(explode(',', $this->request->get('include', '')));
    }

    /**
     * Get all filters from request.
     *
     * @return array
     */
    public function filters()
    {
        return array_filter($this->request->get('filter', []));
    }

    /**
     * Get all filters from request.
     *
     * @return array
     */
    public function sorts()
    {
        $sortsSourceArr = array_filter(explode(',', $this->request->get('sort', '')));
        $sortsArr = [];

        while ($sort = array_pop($sortsSourceArr)) {
            $attribute = $sort;
            $direction = $sort[0] === '-' ? 'desc' : 'asc';

            if ($direction === 'desc') {
                $attribute = ltrim($attribute, '-');
            }

            $sortsArr[$attribute] = $direction;
        }

        return $sortsArr;
    }

    /**
     * Get all fields from request.
     *
     * @return array
     */
    public function fields()
    {
        return array_filter($this->request->get('fields', []));
    }

    public function query()
    {
        return $this->query;
    }

    public function allowSort($attribute, $directions = '*')
    {
        if ($attribute instanceof AllowedSort) {
            $this->allowedSorts = array_merge($this->allowedSorts, $attribute->toArray());
        }

        $this->allowedSorts[$attribute] = $directions;
    }

    /**
     * Allow filter by attribute and pattern of value(s).
     *
     * @param  string|\OpenSoutheners\LaravelApiable\Http\AllowedFilter  $attribute
     * @param  string|array<string>  $value
     * @return $this
     */
    public function allowFilter($attribute, $value = '*')
    {
        $this->allowedFilters = array_merge_recursive(
            $this->allowedFilters,
            (
                $attribute instanceof AllowedFilter
                    ? $attribute
                    : AllowedFilter::make($attribute, $value)
            )->toArray()
        );

        return $this;
    }

    /**
     * Allow sparse fields (attributes) for a specific resource type.
     *
     * @param  string  $type
     * @param  array  $attributes
     * @return $this
     */
    public function allowFields($type, array $attributes = ['*'])
    {
        $this->allowedFields[$type] = $attributes;

        return $this;
    }

    /**
     * Alias for allowFields.
     *
     * @param  string  $type
     * @param  array  $attributes
     * @return $this
     */
    public function allowAppends($type, array $attributes = ['*'])
    {
        return $this->allowFields($type, $attributes);
    }

    public function allowInclude($relationship)
    {
        $this->allowedIncludes[] = $relationship;

        return $this;
    }

    public function getAllowedFields()
    {
        return $this->allowedFields;
    }

    public function getAllowedFilters()
    {
        return $this->allowedFilters;
    }

    public function getAllowedSorts()
    {
        return $this->allowedSorts;
    }

    public function getAllowedIncludes()
    {
        return $this->allowedIncludes;
    }
}
