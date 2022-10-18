<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\Support\Apiable;

class AllowedFilter implements Arrayable
{
    public const SIMILAR = 1;

    public const EXACT = 2;

    public const SCOPE = 3;

    public const LOWER_THAN = 4;

    public const LOWER_OR_EQUAL_THAN = 5;

    public const GREATER_THAN = 6;

    public const GREATER_OR_EQUAL_THAN = 7;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var int
     */
    protected $operator;

    /**
     * @var string|array<string>
     */
    protected $values;

    /**
     * Make an instance of this class.
     *
     * @param  string  $attribute
     * @param  int|array<int>|null  $operator
     * @param  string|array<string>  $values
     * @return void
     */
    public function __construct($attribute, $operator = null, $values = '*')
    {
        if (! is_null($operator) && ! $this->isValidOperator($operator)) {
            throw new \Exception(
                sprintf('Operator value "%s" for filtered attribute "%s" is not valid', $operator, $attribute)
            );
        }

        $this->attribute = $attribute;
        $this->operator = $operator ?? Apiable::config('requests.filters.default_operator') ?? static::SIMILAR;
        $this->values = $values;
    }

    /**
     * Allow default filter by attribute
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     * @return static
     */
    public static function make($attribute, $values = '*')
    {
        return new static($attribute, null, $values);
    }

    /**
     * Allow exact attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     * @return static
     */
    public static function exact($attribute, $values = '*')
    {
        return new static($attribute, static::EXACT, $values);
    }

    /**
     * Allow similar attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     * @return static
     */
    public static function similar($attribute, $values = '*')
    {
        return new static($attribute, static::SIMILAR, $values);
    }

    /**
     * Allow greater than attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     * @return static
     */
    public static function greaterThan($attribute, $values = '*')
    {
        return new static($attribute, static::GREATER_THAN, $values);
    }

    /**
     * Allow greater or equal than attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     * @return static
     */
    public static function greaterOrEqualThan($attribute, $values = '*')
    {
        return new static($attribute, static::GREATER_OR_EQUAL_THAN, $values);
    }

    /**
     * Allow lower than attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     * @return static
     */
    public static function lowerThan($attribute, $values = '*')
    {
        return new static($attribute, static::LOWER_THAN, $values);
    }

    /**
     * Allow lower or equal than attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     * @return static
     */
    public static function lowerOrEqualThan($attribute, $values = '*')
    {
        return new static($attribute, static::LOWER_OR_EQUAL_THAN, $values);
    }

    /**
     * Allow similar attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     * @return static
     */
    public static function scoped($attribute, $values = '1')
    {
        return new static(
            Apiable::config('requests.filters.enforce_scoped_names') ? Apiable::scopedFilterSuffix($attribute) : $attribute,
            static::SCOPE,
            $values
        );
    }

    /**
     * Check if passed operators are valid.
     *
     * @param  int|array  $value
     * @return bool
     */
    protected function isValidOperator($value)
    {
        $valuesArr = (array) $value;

        return count(array_intersect($valuesArr, [
            static::SIMILAR,
            static::EXACT,
            static::SCOPE,
            static::LOWER_THAN,
            static::GREATER_THAN,
            static::LOWER_OR_EQUAL_THAN,
            static::GREATER_OR_EQUAL_THAN,
        ])) === count($valuesArr);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, array<string, array<string>>>
     */
    public function toArray()
    {
        $operators = [];

        foreach ((array) $this->operator as $operator) {
            $operators[] = match ($operator) {
                static::EXACT => 'equal',
                static::SCOPE => 'scope',
                static::SIMILAR => 'like',
                static::LOWER_THAN => 'lt',
                static::GREATER_THAN => 'gt',
                static::LOWER_OR_EQUAL_THAN => 'lte',
                static::GREATER_OR_EQUAL_THAN => 'gte',
            };
        }

        return [
            $this->attribute => [
                'operator' => count($operators) === 1 ? $operators[0] : $operators,
                'values' => $this->values,
            ],
        ];
    }
}
