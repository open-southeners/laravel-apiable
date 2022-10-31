<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;
use OpenSoutheners\LaravelApiable\Support\Apiable;
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
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $request
     * @param  \Closure  $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $request, Closure $next)
    {
        if (empty($request->filters())) {
            return $next($request);
        }

        $this->allowed = $request->getAllowedFilters();

        // We need this to be able to add withWhereHas at this step
        $this->includes = $request->includes();

        $this->applyFilters($request->query, $request->userAllowedFilters());

        return $next($request);
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
        $enforceScopeNames = Apiable::config('requests.filters.enforce_scoped_names');
        
        foreach ($filters as $filterAttribute => $filterValues) {
            $this->wrapIfRelatedQuery(function ($query, $attribute, $relationship) use ($filterValues, $enforceScopeNames) {
                $queryModel = $query->getModel();

                if ($this->isAttribute($queryModel, $attribute)) {
                    return $this->applyArrayOfFiltersToQuery($query, $attribute, (array) $filterValues, $relationship);
                }

                $scopeName = Str::camel($enforceScopeNames ? str_replace('_scoped', '', $attribute) : $attribute);

                if ($this->isScope($queryModel, $scopeName)) {
                    return $this->forwardCallTo($query, $scopeName, (array) $filterValues);
                }
            }, $query, $filterAttribute);
        }

        return $query;
    }

    /**
     * Wrap query if relationship found in filter's attribute.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Builder, string, string): mixed  $callback
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $filterAttribute
     * @return mixed
     */
    protected function wrapIfRelatedQuery(callable $callback, Builder $query, string $filterAttribute)
    {
        if (! str_contains($filterAttribute, '.')) {
            return $callback($query, null, $filterAttribute);
        }

        $attributePartsArr = explode('.', $filterAttribute);

        $attribute = array_pop($attributePartsArr);

        $relationship = implode($attributePartsArr);

        if (in_array($relationship, $this->includes) && version_compare(App::version(), '9.16.0', '>=')) {
            return $query->withWhereHas($relationship, fn ($query) => $callback($query, $attribute, $relationship));
        }

        return $query->whereHas($relationship, fn ($query) => $callback($query, $attribute, $relationship));
    }

    /**
     * Applies where/orWhere to all filtered values.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $attribute
     * @param  array  $filterValues
     * @return void
     */
    protected function applyArrayOfFiltersToQuery($query, string $attribute, array $filterValues, $relationship = null)
    {
        $hasModifiers = Arr::isAssoc($filterValues);

        for ($i = 0; $i < count($filterValues); $i++) {
            $filterValue = array_values($filterValues)[$i];
            $filterOperator = array_keys($filterValues)[$i];
            $filterBoolean = $i === 0 || $hasModifiers ? 'and' : 'or';

            $fullAttribute = $relationship ? "${relationship}.${attribute}" : $attribute;

            if (! is_string($filterOperator)) {
                $filterOperator = $this->allowed[$fullAttribute]['operator'];
            }

            if ($filterOperator === 'like') {
                $filterValue = "%${filterValue}%";
            }

            $query->where(
                $query->getModel()->getTable().".$attribute",
                match ($filterOperator) {
                    'gt' => '>',
                    'gte' => '>=',
                    'lt' => '<',
                    'lte' => '<=',
                    'like' => 'LIKE',
                    'equal' => '=',
                },
                $filterValue,
                $filterBoolean
            );
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
