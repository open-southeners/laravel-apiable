<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;

class AllowedInclude implements Arrayable
{
    /**
     * Make an instance of this class.
     *
     * @param  string|string[]  $relationship
     * @return void
     */
    public function __construct(protected string|array $relationship)
    {
        //
    }

    /**
     * Allow include resource relationship.
     *
     * @param  string|string[]  $relationship
     */
    public static function make(string|array $relationship): self
    {
        return new self($relationship);
    }

    /**
     * Get the instance as an array.
     *
     * @return string[]
     */
    public function toArray(): array
    {
        return (array) $this->relationship;
    }
}
