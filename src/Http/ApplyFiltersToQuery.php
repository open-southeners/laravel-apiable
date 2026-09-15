<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;
use OpenSoutheners\LaravelApiable\Support\Apiable;

use function OpenSoutheners\ExtendedPhp\Classes\class_namespace;

class ApplyFiltersToQuery implements HandlesRequestQueries
{
    use ForwardsCalls;

    /**
     * @var array<array>
     */
    protected array $allowed = [];

    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  Closure(RequestQueryObject): Builder  $next
     * @return Builder
     */
    public function from(RequestQueryObject $request, Closure $next)
    {
        $this->allowed = $request->getAllowedFilters();

        $userFilters = $request->userAllowedFilters() ?: $request->getDefaultFilters();

        $this->applyFilters($request->query, $userFilters);

        return $next($request);
    }

    /**
     * Apply collection of filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $enforceScopeNames = Apiable::config('requests.filters.enforce_scoped_names');

        foreach ($filters as $filterAttribute => $filterValues) {
            if (empty($filterValues)) {
                continue;
            }

            $allowedOperator = $this->allowed[$filterAttribute]['operator'] ?? null;

            // Scope filters always pass all values in a single scope call as positional arguments.
            // Named argument format (e.g. filter[scope][arg1]=hello) and repeated key format
            // (e.g. filter[scope]=hello&filter[scope]=world) are both supported.
            if ($allowedOperator === 'scope') {
                $this->applyScopeWithNamedArguments($query, $filterAttribute, $filterValues, $enforceScopeNames);

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
     * Apply a scope filter passing all named argument values as a single scope call.
     */
    protected function applyScopeWithNamedArguments(Builder $query, string $filterAttribute, array $filterValues, bool $enforceScopeNames): void
    {
        $attributePartsArr = explode('.', $filterAttribute);
        $attribute = array_pop($attributePartsArr);
        $relationship = implode($attributePartsArr);

        $scopeName = Str::camel($enforceScopeNames ? str_replace('_scoped', '', $attribute) : $attribute);

        $scopeArgs = array_values(array_map(
            fn ($value) => is_array($value) ? reset($value) : $value,
            $filterValues
        ));

        $wrappedQueryFn = function ($query) use ($scopeName, $scopeArgs) {
            if ($this->isScope($query->getModel(), $scopeName)) {
                $this->forwardCallTo($query, $scopeName, $scopeArgs);
            }
        };

        if ($relationship) {
            $query->has($relationship, callback: $wrappedQueryFn);
        } else {
            $query->where(column: $wrappedQueryFn);
        }
    }

    /**
     * Wrap query if relationship found applying its operator and conditional to the filtered attribute.
     *
     * An attribute can carry more than one allowed operator (e.g. gte + lte for a range filter).
     * When that's the case, `$this->allowed[$filterAttribute]['operator']` is an operator-keyed
     * map (see AllowsFilters::mergeFilterOperators()) instead of a single scalar string: each
     * filter value is resolved to its own operator (`filter[attribute][gte]=`) rather than all
     * values sharing whichever operator happened to be registered last.
     *
     * @param  callable(Builder, string|null, string, string, string, string): mixed  $callback
     * @param  array<int|string, list<string>|array<string>|string>|string  $filterValues
     */
    protected function wrapIfRelatedQuery(callable $callback, Builder $query, string $filterAttribute, array|string $filterValues): void
    {
        $operatorRule = $this->allowed[$filterAttribute]['operator'] ?? null;
        $systemPreferredOperator = is_array($operatorRule) ? array_key_first($operatorRule) : $operatorRule;

        $attributePartsArr = explode('.', $filterAttribute);

        $attribute = array_pop($attributePartsArr);

        $relationship = implode($attributePartsArr);

        $outerKeys = array_keys($filterValues);
        $outerValues = array_values($filterValues);

        for ($i = 0; $i < count($filterValues); $i++) {
            $filterValue = $outerValues[$i];

            // Default filters are keyed directly by operator (e.g. ['equal' => 'published']),
            // while user-submitted filters with an explicit operator key are a list of single
            // pair arrays (e.g. [['gte' => '2024-01-01'], ['lte' => '2024-01-31']]) — the
            // operator lives on the inner key, not this outer list's (numeric) index. That
            // wrapper is only ever an *associative* (string-keyed) array; with
            // `strict_comma_encoding` on, an operator-less/default value can itself already be a
            // plain `list<string>` of pre-split values (AllowsFilters::operatorFilterValuesMatchRules()),
            // which must not be mistaken for the wrapper.
            $isOperatorWrapper = is_array($filterValue) && ! array_is_list($filterValue);

            $operatorKey = is_string($outerKeys[$i])
                ? $outerKeys[$i]
                : ($isOperatorWrapper ? array_key_first($filterValue) : $systemPreferredOperator);

            $rawValue = $isOperatorWrapper ? reset($filterValue) : $filterValue;

            // With `strict_comma_encoding` on, `$rawValue` already arrives as the final
            // already-split `list<string>` — re-exploding it on comma here would wrongly split a
            // literal comma the parser correctly preserved inside one value.
            //
            // Only a genuinely empty string is dropped (e.g. the gap left by a leading/repeated
            // comma such as `filter[a]=x,,y`). A whitespace-only value (`' '`) is a real value a
            // client can legitimately filter by and must survive — trimming it away here made it
            // indistinguishable from an empty one, with no way to opt out.
            $values = is_array($rawValue)
                ? array_values(array_filter(
                    $rawValue,
                    fn ($value) => (string) $value !== ''
                ))
                : array_values(array_filter(
                    explode(',', (string) $rawValue),
                    fn ($value) => (string) $value !== ''
                ));

            $operator = $this->sqlOperatorFor($operatorKey);

            $negated = in_array($operatorKey, ['not_equal', 'not_like'], true);

            $query->where(function (Builder $query) use ($callback, $relationship, $attribute, $operator, $values, $negated) {
                for ($n = 0; $n < count($values); $n++) {
                    $condition = $n === 0 || $negated ? 'and' : 'or';

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
     * Resolve an operator key (e.g. "gte", "equal") into its SQL comparison operator.
     *
     * Falls back to the configured default operator for an unrecognised/missing key instead of
     * leaking the raw operator key (or the numeric config value) into the query builder, which
     * used to make Eloquent treat it as an invalid operator and silently rewrite the query into
     * `WHERE attribute = 1`.
     */
    protected function sqlOperatorFor(?string $operatorKey): string
    {
        $sqlOperators = [
            'gt' => '>',
            'gte' => '>=',
            'lt' => '<',
            'lte' => '<=',
            'like' => 'LIKE',
            'not_like' => 'NOT LIKE',
            'equal' => '=',
            'not_equal' => '!=',
        ];

        if ($operatorKey !== null && isset($sqlOperators[$operatorKey])) {
            return $sqlOperators[$operatorKey];
        }

        $defaultOperatorKey = AllowedFilter::operatorKey((int) Apiable::config('requests.filters.default_operator'));

        return $sqlOperators[$defaultOperatorKey] ?? 'LIKE';
    }

    /**
     * Apply where or orWhere (non relationships only) to all filtered values.
     *
     * @param  Builder|Relation  $query
     */
    protected function applyFilterAsWhere($query, $relationship, string $attribute, string $operator, string $value, string $condition): void
    {
        $query->where(
            $query->getModel()->getTable().".{$attribute}",
            $operator,
            in_array($operator, ['LIKE', 'NOT LIKE'], true) ? "%{$value}%" : $value,
            $relationship ? 'and' : $condition
        );
    }

    /**
     * Apply scope wrapped into a where (non relationships only) forwarding the call directly to the builder.
     *
     * @param  Builder|Relation  $query
     */
    protected function applyFilterAsScope($query, $relationship, string $scope, string $operator, string $value, string $condition): void
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
     */
    protected function isAttribute(Model $model, mixed $value): bool
    {
        return in_array($value, Schema::getColumnListing($model->getTable()));
    }

    /**
     * Check if the specified filter is a model scope.
     */
    protected function isScope(Model $model, mixed $value): bool
    {
        $isScope = $model->hasNamedScope($value);
        $modelQueryBuilder = $model::query();

        if (! $isScope && class_namespace($modelQueryBuilder) !== 'Illuminate\Database\Eloquent') {
            return in_array(
                $value,
                array_diff(
                    get_class_methods($modelQueryBuilder),
                    get_class_methods(Builder::class)
                )
            );
        }

        return $isScope;
    }
}
