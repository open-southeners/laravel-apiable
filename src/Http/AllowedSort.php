<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\Support\Apiable;

class AllowedSort implements Arrayable
{
    public const BOTH = 1;

    public const ASCENDANT = 2;

    public const DESCENDANT = 3;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $direction;

    /**
     * Make an instance of this class.
     *
     * @param  string  $attribute
     * @param  int|null  $direction
     * @return void
     */
    public function __construct($attribute, $direction = null)
    {
        $this->attribute = $attribute;
        $this->direction = $direction ?? Apiable::config('requests.sorts.default_direction') ?? static::BOTH;
    }

    /**
     * Allow default sort by attribute.
     *
     * @param  string  $attribute
     * @return static
     */
    public static function make($attribute)
    {
        return new static($attribute);
    }

    /**
     * Allow sort by attribute as ascendant.
     *
     * @param  string  $attribute
     * @return static
     */
    public static function ascendant($attribute)
    {
        return new static($attribute, static::ASCENDANT);
    }

    /**
     * Allow sort by attribute as descendant.
     *
     * @param  string  $attribute
     * @return static
     */
    public static function descendant($attribute)
    {
        return new static($attribute, static::DESCENDANT);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, string>
     */
    public function toArray()
    {
        return [
            $this->attribute => match ($this->direction) {
                default => '*',
                AllowedSort::BOTH => '*',
                AllowedSort::ASCENDANT => 'asc',
                AllowedSort::DESCENDANT => 'desc',
            },
        ];
    }
}
