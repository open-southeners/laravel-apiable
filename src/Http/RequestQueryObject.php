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

    /**
     * Process query object allowing the following user operations.
     *
     * @param  array  $alloweds
     * @return $this
     */
    public function allowing(array $alloweds)
    {
        foreach ($alloweds as $allowed) {
            match (get_class($allowed)) {
                AllowedSort::class => $this->allowSort($allowed),
                AllowedFilter::class => $this->allowFilter($allowed),
                AllowedInclude::class => $this->allowInclude($allowed),
                AllowedFields::class => $this->allowFields($allowed),
            };
        }

        return $this;
    }

    /**
     * Allow sorting by the following attribute and direction.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedSort|string  $attribute
     * @param  string  $direction
     * @return void
     */
    public function allowSort($attribute, $direction = '*')
    {
        if ($attribute instanceof AllowedSort) {
            $this->allowedSorts = array_merge($this->allowedSorts, $attribute->toArray());
        } else {
            $this->allowedSorts[$attribute] = $direction;
        }

        return $this;
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
     * Allow sparse fields (attributes) for a specific resource type.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedFields|string  $type
     * @param  array<string>|string  $attributes
     * @return $this
     */
    public function allowFields($type, $attributes)
    {
        if ($type instanceof AllowedFields) {
            $this->allowedFields = array_merge($this->allowedFields, $type->toArray());
        } else {
            $this->allowedFields[$type] = [$attributes];
        }

        return $this;
    }

    /**
     * Alias for allowFields.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedFields|string  $type
     * @param  array  $attributes
     * @return $this
     */
    public function allowAppends($type, array $attributes = ['*'])
    {
        return $this->allowFields($type, $attributes);
    }

    /**
     * Allow include relationship to the response.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedInclude|string  $relationship
     * @return $this
     */
    public function allowInclude($relationship)
    {
        $this->allowedIncludes[] = (string) $relationship;

        return $this;
    }

    /**
     * Get list of allowed fields.
     *
     * @return array<string, string>
     */
    public function getAllowedFields()
    {
        return $this->allowedFields;
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

    /**
     * Get list of allowed sorts.
     *
     * @return array<string, string>
     */
    public function getAllowedSorts()
    {
        return $this->allowedSorts;
    }

    /**
     * Get list of allowed includes.
     *
     * @return array<string>
     */
    public function getAllowedIncludes()
    {
        return $this->allowedIncludes;
    }
}
