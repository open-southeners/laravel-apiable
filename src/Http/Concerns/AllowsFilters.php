<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\DefaultFilter;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @mixin RequestQueryObject
 */
trait AllowsFilters
{
    /**
     * @var array<string, array>
     */
    protected array $allowedFilters = [];

    /**
     * @var array<string, array>
     */
    protected array $defaultFilters = [];

    /**
     * Get user filters from request.
     *
     * Each attribute maps to its list of filter entries as parsed by flex-url: a bracket-less
     * `filter[attribute]=` reads as one entry with an empty `operator`, `filter[attribute][op]=`
     * as one entry per operator (or, for a scoped filter's named arguments, one entry per
     * argument key). `values` is a `list<string>` already split on comma — respecting
     * `strict_comma_encoding` — rather than a comma-joined string.
     *
     * @return array<string, list<array{operator: string, values: list<string>}>>
     */
    public function filters(): array
    {
        $filters = [];

        foreach ($this->flexUrl()->getFilters() as $entry) {
            $filters[$entry['attribute']][] = ['operator' => $entry['operator'], 'values' => $entry['values']];
        }

        return $filters;
    }

    /**
     * Allow filter by attribute and pattern of value(s).
     *
     * @param  AllowedFilter|string  $attribute
     * @param  array<string>|string|int  $operator
     * @param  array<string>|string  $values
     */
    public function allowFilter($attribute, $operator = ['*'], $values = ['*']): static
    {
        if ($values === ['*'] && (is_array($operator) || is_string($operator))) {
            $values = $operator;

            $operator = null;
        }

        $this->allowedFilters = $this->mergeFilterOperators(
            $this->allowedFilters,
            $attribute instanceof AllowedFilter
                ? $attribute->toArray()
                : (new AllowedFilter($attribute, $operator, $values))->toArray()
        );

        return $this;
    }

    /**
     * Default filter by the following attribute and direction when no user filters are being applied.
     *
     * @param  DefaultFilter|string  $attribute
     * @param  array<string>|string|int  $operator
     * @param  array<string>|string  $values
     */
    public function applyDefaultFilter($attribute, $operator = ['*'], $values = ['*']): static
    {
        if ($values === ['*'] && (is_array($operator) || is_string($operator))) {
            $values = $operator;

            $operator = null;
        }

        // A default may be declared both as a controller attribute and fluently.
        // Replacing an operator's value keeps that registration idempotent while
        // retaining defaults registered for other operators on the same field.
        $this->defaultFilters = array_replace_recursive(
            $this->defaultFilters,
            $attribute instanceof DefaultFilter
                ? $attribute->toArray()
                : (new DefaultFilter($attribute, $operator, $values))->toArray()
        );

        return $this;
    }

    /**
     * Allow filter by scope and pattern of value(s).
     *
     * @param  array<string>|string  $value
     */
    public function allowScopedFilter(string $attribute, array|string $value = '*'): static
    {
        $this->allowedFilters = $this->mergeFilterOperators(
            $this->allowedFilters,
            AllowedFilter::scoped($attribute, $value)->toArray()
        );

        return $this;
    }

    /**
     * Merge an allowed filter definition (as produced by AllowedFilter::toArray()) into the
     * given allowed filters map, combining every operator registered for the same attribute
     * across multiple allowFilter()/allowing() calls instead of corrupting them the way
     * array_merge_recursive() used to (it turned a repeated "operator" key into a mangled
     * array, e.g. registering gte and lte on the same attribute broke both).
     *
     * A single-operator attribute keeps the original flat shape
     * (`['operator' => 'gte', 'values' => '*']`) for backwards compatibility. An attribute with
     * more than one registered operator is stored as an operator-keyed map of value patterns
     * (`['operator' => ['gte' => '*', 'lte' => '*']]`), preserving each operator's own value
     * restriction and remembering the first-registered operator as the default (used for a
     * plain `filter[attribute]=value` with no explicit operator key).
     *
     * @return array<string, array>
     */
    protected function mergeFilterOperators(array $allowedFilters, array $definition): array
    {
        foreach ($definition as $attribute => $entry) {
            $operators = array_merge(
                isset($allowedFilters[$attribute]) ? $this->operatorPatternsFromFilterEntry($allowedFilters[$attribute]) : [],
                $this->operatorPatternsFromFilterEntry($entry)
            );

            $allowedFilters[$attribute] = count($operators) === 1
                ? ['operator' => array_key_first($operators), 'values' => reset($operators)]
                : ['operator' => $operators];
        }

        return $allowedFilters;
    }

    /**
     * Normalise a filter rule/definition entry into a map of operator key => value pattern,
     * regardless of whether it's a single-operator flat entry, a single AllowedFilter
     * registered with several operators sharing one value pattern, or an already-merged
     * operator-keyed map.
     *
     * @return array<string, array<string>|string>
     */
    protected function operatorPatternsFromFilterEntry(array $entry): array
    {
        $operator = $entry['operator'] ?? null;

        if (is_array($operator) && Arr::isAssoc($operator)) {
            return $operator;
        }

        $pattern = $entry['values'] ?? '*';
        $operatorKeys = (array) ($operator ?? AllowedFilter::operatorKey((int) Apiable::config('requests.filters.default_operator')));

        $patterns = [];

        foreach ($operatorKeys as $operatorKey) {
            $patterns[$operatorKey] = $pattern;
        }

        return $patterns;
    }

    /**
     * Get user requested filters filtered by allowed ones.
     */
    public function userAllowedFilters(): array
    {
        $throwOnValidationError = fn ($key) => throw new HttpException(400, sprintf('"%s" is not filterable or contains invalid values', $key));

        return $this->validator($this->filters())
            ->givingRules($this->allowedFilters)
            ->when(function ($key, $modifiers, $values, $rules, &$valids) {
                $values = (array) $values;

                return ($rules['operator'] ?? null) === 'scope'
                    ? $this->scopedFilterValuesMatchRules($values, $rules['values'] ?? '1', $valids)
                    : $this->operatorFilterValuesMatchRules($values, $rules, $valids);
            }, $throwOnValidationError)
            ->validate();
    }

    /**
     * Validate (and select) the operator + value(s) for each requested filter entry against
     * the attribute's registered operator(s), matching each already-split value against the
     * operator's value pattern (unmatched values are dropped rather than invalidating the whole
     * entry) and rejecting any operator key that isn't registered for the attribute (e.g.
     * `filter[due_at][lte]=` when only `gte` was allowed).
     *
     * `$values` arrives pre-split by flex-url's parser (see `RequestQueryObject::filters()`), so
     * there's no comma explode/rejoin here: with `requests.strict_comma_encoding` off (the
     * default), the valid parts are rejoined into a single comma-joined string — preserving the
     * pre-flex-url output shape byte-for-byte, since a comma is always a plain separator in that
     * mode and rejoining is lossless. With it on, the valid parts are kept as a real array so a
     * literal comma inside one value survives instead of being rejoined then wrongly re-split
     * downstream (see `ApplyFiltersToQuery::wrapIfRelatedQuery()`).
     *
     * @param  list<array{operator: string, values: list<string>}>  $values
     * @param  array<string, mixed>  $rules
     * @param  array<int, array|string>  $valids
     */
    protected function operatorFilterValuesMatchRules(array $values, array $rules, array &$valids): bool
    {
        $operatorPatterns = $this->operatorPatternsFromFilterEntry($rules);
        $defaultOperator = array_key_first($operatorPatterns);
        $strict = (bool) Apiable::config('requests.strict_comma_encoding');

        $valids = [];
        $allValid = true;

        foreach ($values as $value) {
            $operatorKey = $value['operator'] !== '' ? $value['operator'] : $defaultOperator;
            $parts = $value['values'];

            if (! array_key_exists($operatorKey, $operatorPatterns)) {
                $allValid = false;

                continue;
            }

            $pattern = $operatorPatterns[$operatorKey];

            $validParts = $pattern === '*'
                ? $parts
                : array_values(array_filter($parts, fn ($part) => Str::is($pattern, (string) $part)));

            if (count($validParts) !== count($parts)) {
                $allValid = false;
            }

            if (empty($validParts)) {
                continue;
            }

            $resultValue = $strict ? $validParts : implode(',', $validParts);

            $valids[] = $value['operator'] !== '' ? [$operatorKey => $resultValue] : $resultValue;
        }

        return $allValid;
    }

    /**
     * Validate scope filter argument(s) against the registered value pattern.
     *
     * Named-argument scope calls (`filter[scope][arg]=value`) validate against an unrestricted
     * pattern by default rather than the truthy `'1'` default (which only ever matches a plain
     * `filter[scope]=1` boolean toggle) unless an explicit pattern was registered.
     *
     * Unlike attribute filters, scope arguments are positional: a partially valid set can't be
     * applied (it would call the scope with the wrong argument order/count), so this is all
     * arguments valid or none applied — never a partial drop. Scope arguments never went through
     * apiable's own comma-splitting, so each entry's (already flex-url-split) values are always
     * rejoined back into a single scalar here too — this keeps `ApplyFiltersToQuery::
     * applyScopeWithNamedArguments()` (which unwraps each argument to a single scalar) unaffected.
     *
     * @param  list<array{operator: string, values: list<string>}>  $values
     * @param  array<string>|string  $pattern
     * @param  array<int, array|string>  $valids
     */
    protected function scopedFilterValuesMatchRules(array $values, $pattern, array &$valids): bool
    {
        $isNamedArguments = ! empty($values) && reset($values)['operator'] !== '';

        if ($isNamedArguments && $pattern === '1') {
            $pattern = '*';
        }

        $allValid = $pattern === '*' || array_reduce(
            $values,
            fn ($carry, $entry) => $carry && array_reduce(
                $entry['values'],
                fn ($carryValue, $value) => $carryValue && Str::is($pattern, $value),
                true
            ),
            true
        );

        $valids = $allValid ? $this->reshapeScopeFilterValues($values) : [];

        return $allValid;
    }

    /**
     * Reshapes flex-url's per-attribute filter entries back into the flat scope-argument shape
     * `userAllowedFilters()`/`ApplyFiltersToQuery::applyScopeWithNamedArguments()` expect: a
     * bracket-less entry's values become individual top-level scalars (matching a repeated
     * `filter[scope]=a&filter[scope]=b`), a named-argument entry becomes a single `[arg => value]`
     * pair.
     *
     * @param  list<array{operator: string, values: list<string>}>  $values
     * @return array<int, array|string>
     */
    protected function reshapeScopeFilterValues(array $values): array
    {
        $valids = [];

        foreach ($values as $entry) {
            if ($entry['operator'] === '') {
                foreach ($entry['values'] as $value) {
                    $valids[] = $value;
                }

                continue;
            }

            $valids[] = [$entry['operator'] => implode(',', $entry['values'])];
        }

        return $valids;
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

    /**
     * Get list of default filters.
     *
     * @return array<string, array>
     */
    public function getDefaultFilters(): array
    {
        return $this->defaultFilters;
    }
}
