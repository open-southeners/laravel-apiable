<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\Support\Apiable;

class AllowedFilter implements Arrayable
{
    public const SIMILAR = 1;

    public const EXACT = 2;

    public const SCOPE = 3;

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
     * @param  int|null  $operator
     * @param  string|array<string>  $values
     * @return void
     */
    public function __construct($attribute, $operator = null, $values = '*')
    {
        if (! is_null($operator) && ! in_array($operator, [static::SIMILAR, static::EXACT, static::SCOPE])) {
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
     * Get the instance as an array.
     *
     * @return array<string, array<string, array<string>>>
     */
    public function toArray()
    {
        return [
            $this->attribute => [
                'operator' => match ($this->operator) {
                    static::EXACT => '=',
                    static::SIMILAR => 'like',
                    static::SCOPE => 'scope'
                },
                'values' => $this->values,
            ],
        ];
    }
}
