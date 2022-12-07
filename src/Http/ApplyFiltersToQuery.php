<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
            if (! $filterValues || empty($filterValues)) {
                continue;
            }

            $this->wrapIfRelatedQuery(function ($query, $relationship, $attribute, $operator, $value, $condition) use ($enforceScopeNames) {
                $scopeName = Str::camel($enforceScopeNames ? str_replace('_scoped', '', $attribute) : $attribute);
                $isAttribute = $this->isAttribute($query->getModel(), $attribute);
                $isScope = $this->isScope($query->getModel(), $scopeName);

                match (true) {
                    $isAttribute => $this->applyFilterAsWhere($query, $relationship, $attribute, $operator, $value, $condition),
                    $isScope => $this->applyFilterAsScope($query, $relationship, $scopeName, $operator, $value, $condition),
                    default => null,
                };
            }, $query, $filterAttribute, $filterValues);
        }

        return $query;
    }

    /**
     * Wrap query if relationship found applying its operator and conditional to the filtered attribute.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Builder, string|null, string, string, string, string): mixed  $callback
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $filterAttribute
     * @param  array<string>|string  $filterValues
     * @return void
     */
    protected function wrapIfRelatedQuery(callable $callback, Builder $query, string $filterAttribute, $filterValues)
    {
        $systemPreferredOperator = $this->allowed[$filterAttribute]['operator'];

        $attributePartsArr = explode('.', $filterAttribute);

        $attribute = array_pop($attributePartsArr);

        $relationship = implode($attributePartsArr);

        for ($i = 0; $i < count($filterValues); $i++) {
            $values = array_filter(explode(',', array_values($filterValues)[$i]));
            $operator = array_keys($filterValues)[$i];

            if (! is_string($operator)) {
                $operator = $systemPreferredOperator;
            }

            $operator = match ($operator) {
                'gt' => '>',
                'gte' => '>=',
                'lt' => '<',
                'lte' => '<=',
                'like' => 'LIKE',
                'equal' => '=',
                default => Apiable::config('requests.filters.default_operator')
            };

            $query->where(function (Builder $query) use ($callback, $relationship, $attribute, $operator, $values) {
                for ($n = 0; $n < count($values); $n++) {
                    $condition = $n === 0 ? 'and' : 'or';

                    if (! $relationship) {
                        $callback($query, $relationship, $attribute, $operator, $values[$n], $condition);

                        continue;
                    }

                    $query->has(
                        relation: $relationship,
                        callback: fn ($query) => $callback($query, $relationship, $attribute, $operator, $values[$n], $condition),
                        boolean: $condition
                    );
                }
            });
        }
    }

    /**
     * Apply where or orWhere (non relationships only) to all filtered values.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation  $query
     * @param  string  $attribute
     * @param  array  $filterValues
     * @return void
     */
    protected function applyFilterAsWhere($query, $relationship, string $attribute, string $operator, string $value, string $condition)
    {
        $query->where(
            $query->getModel()->getTable().".${attribute}",
            $operator,
            $operator === 'LIKE' ? "%${value}%" : $value,
            $relationship ? 'and' : $condition
        );
    }

    /**
     * Apply scope wrapped into a where (non relationships only) forwarding the call directly to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation  $query
     * @param  string|null  $relationship
     * @param  string  $scope
     * @param  string  $operator
     * @param  string  $value
     * @param  string  $condition
     * @return void
     */
    protected function applyFilterAsScope($query, $relationship, string $scope, string $operator, string $value, string $condition)
    {
        $wrappedQueryFn = fn ($query) => $this->forwardCallTo($query, $scope, (array) $value);

        if ($relationship) {
            $wrappedQueryFn($query);

            return;
        }

        $query->where(
            column: $wrappedQueryFn,
            boolean: $condition
        );
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

        if (! $isScope && class_namespace($modelQueryBuilder) !== 'Illuminate\Database\Eloquent') {
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
