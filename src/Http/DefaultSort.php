<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;

class DefaultSort implements Arrayable
{
    public const ASCENDANT = 1;

    public const DESCENDANT = 2;

    protected string $attribute;

    protected int $direction;

    /**
     * Make an instance of this class.
     */
    public function __construct(string $attribute, ?int $direction = null)
    {
        $this->attribute = $attribute;
        $this->direction = $direction ?? static::ASCENDANT;
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
                default => 'asc',
                static::ASCENDANT => 'asc',
                static::DESCENDANT => 'desc',
            },
        ];
    }
}
