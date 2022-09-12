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
     * @return static
     */
    public static function make($attribute, $values = '*')
    {
        $defaultOperator = Apiable::config('filters.default_operator') ?? 'like';

        return new static($attribute, $defaultOperator, $values);
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
        return new static($attribute, '=', $values);
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
        return new static($attribute, 'like', $values);
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
            'scope',
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
                $this->operator => $this->values,
            ],
        ];
    }
}
