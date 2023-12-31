<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\Support\Apiable;

class AllowedSort implements Arrayable
{
    public const BOTH = 1;

    public const ASCENDANT = 2;

    public const DESCENDANT = 3;

    protected string $attribute;

    protected int $direction;

    /**
     * Make an instance of this class.
     *
     * @return void
     */
    public function __construct(string $attribute, int $direction = null)
    {
        $this->attribute = $attribute;
        $this->direction = (int) ($direction ?? Apiable::config('requests.sorts.default_direction') ?? static::BOTH);
    }

    /**
     * Allow default sort by attribute.
     */
    public static function make(string $attribute): self
    {
        return new self($attribute);
    }

    /**
     * Allow sort by attribute as ascendant.
     */
    public static function ascendant(string $attribute): self
    {
        return new self($attribute, static::ASCENDANT);
    }

    /**
     * Allow sort by attribute as descendant.
     */
    public static function descendant(string $attribute): self
    {
        return new self($attribute, static::DESCENDANT);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, string>
     */
    public function toArray(): array
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
