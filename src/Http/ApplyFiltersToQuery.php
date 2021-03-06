<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;
use function OpenSoutheners\LaravelHelpers\Classes\class_namespace;

class ApplyFiltersToQuery implements HandlesRequestQueries
{
    use ForwardsCalls;

    /**
     * @var array<array>
     */
    protected $allowed = [];

    /**
     * @var array
     */
    protected $includes = [];

    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $requestQueryObject
     * @param  \Closure  $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $requestQueryObject, Closure $next)
    {
        $filters = $requestQueryObject->filters();

        $this->includes = $requestQueryObject->includes();

        if (empty($filters)) {
            return $next($requestQueryObject);
        }

        $this->allowed = $requestQueryObject->getAllowedFilters();

        $this->applyFilters(
            $requestQueryObject->query,
            $this->getUserFilters($filters)
        );

        return $next($requestQueryObject);
    }

    protected function getUserFilters(array $filters)
    {
        $filteredFilterValues = [];

        foreach ($filters as $attribute => $filterValues) {
            $allowedByAttribute = array_key_exists($attribute, $this->allowed);

            if (! isset($this->allowed[$attribute])) {
                continue;
            }

            $allowedAttributeValues = head($this->allowed[$attribute]);

            if (is_string($filterValues) && is_string($allowedAttributeValues) && $filterValues === $allowedAttributeValues) {
                $filteredFilterValues[$attribute] = $filterValues;

                continue;
            }

            // All filter values are valid, no modification needed
            if ($allowedByAttribute && $allowedAttributeValues === '*') {
                $filteredFilterValues[$attribute] = $filterValues;

                continue;
            }

            // Some filter values are valid, intersect those valid ones
            if ($allowedByAttribute && is_array($allowedAttributeValues)) {
                $filteredFilterValues[$attribute] = array_intersect($allowedAttributeValues, explode(',', $filterValues));

                continue;
            }
        }

        return array_filter($filteredFilterValues);
    }

    /**
     * Apply collection of filters to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters(Builder $query, array $filters)
    {
        foreach ($filters as $fullAttribute => $values) {
            $this->wrapIfRelatedQuery(function ($query, $attribute) use ($fullAttribute, $values) {
                $queryModel = $query->getModel();

                if ($this->isAttribute($queryModel, $attribute)) {
                    return $this->applyArrayOfFiltersToQuery($query, $attribute, (array) $values, $fullAttribute);
                }

                $scopeFn = Str::camel($attribute);

                if ($this->isScope($queryModel, $scopeFn)) {
                    return $this->forwardCallTo($query, $scopeFn, $values);
                }
            }, $query, $fullAttribute);
        }

        return $query;
    }

    /**
     * Wrap query if relationship found in filter's attribute.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Builder, string): mixed  $callback
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $attribute
     * @return mixed
     */
    protected function wrapIfRelatedQuery(callable $callback, Builder $query, string $attribute)
    {
        if (! str_contains($attribute, '.')) {
            return $callback($query, $attribute);
        }

        $attributePartsArr = explode('.', $attribute);

        $relationshipAttribute = array_pop($attributePartsArr);

        $relationship = implode($attributePartsArr);

        if (in_array($relationship, $this->includes) && version_compare(App::version(), '9.16.0', '>=')) {
            return $query->withWhereHas($relationship, function ($query) use ($callback, $relationshipAttribute) {
                return $callback($query, $relationshipAttribute);
            });
        }

        return $query->whereHas($relationship, function ($query) use ($callback, $relationshipAttribute) {
            return $callback($query, $relationshipAttribute);
        });
    }

    /**
     * Applies where/orWhere to all filtered values.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $attribute
     * @param  array  $filterValues
     * @return void
     */
    protected function applyArrayOfFiltersToQuery($query, string $attribute, array $filterValues, string $fullAttribute)
    {
        for ($i = 0; $i < count($filterValues); $i++) {
            $filterValue = $filterValues[$i];
            $filterOperator = array_keys($this->allowed[$fullAttribute])[0];

            if ($filterOperator === 'like') {
                $filterValue = "%${filterValue}%";
            }

            $query->where($attribute, $filterOperator, $filterValue, $i === 0 ? 'and' : 'or');
        }
    }

    /**
     * Check if the specified filter is a model attribute.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  mixed  $value
     * @return bool
     */
    protected function isAttribute(Model $model, $value)
    {
        return in_array($value, Schema::getColumnListing($model->getTable()));
    }

    /**
     * Check if the specified filter is a model scope.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  mixed  $value
     * @return bool
     */
    protected function isScope(Model $model, $value)
    {
        $isScope = $model->hasNamedScope($value);
        $modelQueryBuilder = $model::query();

        if ($isScope && class_namespace($modelQueryBuilder) !== 'Illuminate\Database\Eloquent') {
            return in_array(
                $value,
                array_diff(
                    get_class_methods($modelQueryBuilder),
                    get_class_methods(get_parent_class($modelQueryBuilder))
                )
            );
        }

        return $isScope;
    }
}
