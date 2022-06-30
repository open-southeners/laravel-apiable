<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\Support\Apiable;

class AllowedFilter implements Arrayable
{
    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
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
     * @param  string  $operator
     * @param  string|array<string>  $values
     * @return void
     */
    public function __construct($attribute, $operator, $values = '*')
    {
        $this->attribute = $attribute;
        $this->operator = $operator;
        $this->values = $values;
    }

    /**
     * Allow default filter by attribute
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     * @return \OpenSoutheners\LaravelApiable\Http\AllowedFilter
     */
    public static function make($attribute, $values = null)
    {
        $defaultOperator = Apiable::config('filters.default_operator') ?? 'like';

        return new self($attribute, $defaultOperator, $values);
    }

    /**
     * Allow exact attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     * @return \OpenSoutheners\LaravelApiable\Http\AllowedFilter
     */
    public static function exact($attribute, $values = null)
    {
        return new self($attribute, '=', $values);
    }

    /**
     * Allow similar attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     * @return \OpenSoutheners\LaravelApiable\Http\AllowedFilter
     */
    public static function similar($attribute, $values = null)
    {
        return new self($attribute, 'like', $values);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<TKey, TValue>
     */
    public function toArray()
    {
        return [
            $this->attribute => [
                $this->operator => $this->values,
            ],
        ];
    }
}
