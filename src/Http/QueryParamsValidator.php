<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class QueryParamsValidator
{
    public const ENFORCE_VALIDATION_STRATEGY = 1;

    public const FILTER_VALIDS_ONLY_STRATEGY = 2;

    /**
     * @var array{0: callable, 1: \Throwable|callable}
     */
    protected $validationCallbacks = [];

    /**
     * Create new instance.
     *
     * @param  array  $params
     * @param  int  $strategy
     * @param  array|bool  $rules
     */
    public function __construct(protected array $params, protected int $strategy, protected $rules = [])
    {
        //
    }

    /**
     * Validate params against the following rules.
     *
     * @param  array|bool  $rules
     * @return $this
     */
    public function givingRules($rules)
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Validate when patterns are matched (using rules instead).
     *
     * @param  callable|\Throwable  $exception
     * @param  array  $patterns
     * @return $this
     */
    public function whenPatternMatches($exception, array $patterns = [])
    {
        return $this->when(function ($key, $modifiers, $values, $rules, &$valids) use ($patterns) {
            $paramPattern = $patterns[$key]['values'] ?? $rules['values'];

            if ($paramPattern === '*') {
                return true;
            }

            $values = (array) $values;

            $valids = array_filter($values, fn ($value) => Str::is($paramPattern, $value));

            return count($values) === count($valids);
        }, $exception);
    }

    /**
     * Validate when condition function passes, throws exception otherwise.
     *
     * @param  \Closure  $condition
     * @param  callable|\Throwable  $exception
     * @return $this
     */
    public function when(Closure $condition, $exception)
    {
        $this->validationCallbacks[] = [$condition, $exception];

        return $this;
    }

    /**
     * Performs validation running all conditions on each query parameter.
     *
     * @return array
     */
    public function validate()
    {
        $filteredResults = [];

        foreach ($this->params as $key => $values) {
            foreach ($this->validationCallbacks as $callback) {
                [$condition, $exception] = $callback;

                $valids = [];
                $rulesForKey = $this->rules === false ? $this->rules : ($this->rules[$key] ?? null);
                $queryParamValues = Arr::isAssoc((array) $values) ? array_values($values) : $values;
                $queryParamModifiers = Arr::isAssoc((array) $values) ? array_keys($values) : [];

                if (is_string($queryParamValues) && Str::contains($queryParamValues, ',')) {
                    $queryParamValues = explode(',', $values);
                }

                $conditionResult = is_null($rulesForKey)
                    ? false
                    : $condition($key, $queryParamModifiers, $queryParamValues, $rulesForKey, $valids);

                if (! $conditionResult && $this->strategy === static::ENFORCE_VALIDATION_STRATEGY) {
                    is_callable($exception) ? $exception($key, $queryParamValues) : throw $exception;
                }

                if ($conditionResult || ! empty($valids)) {
                    $filteredResults[$key] = ! empty($valids) ? $valids : $values;
                }
            }
        }

        return $filteredResults;
    }
}
