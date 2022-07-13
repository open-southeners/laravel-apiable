<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;

class AllowedInclude implements Arrayable
{
    /**
     * @var string|array
     */
    protected $relationship;

    /**
     * Make an instance of this class.
     *
     * @param  string|array  $relationship
     * @return void
     */
    public function __construct($relationship)
    {
        $this->relationship = $relationship;
    }

    /**
     * Allow include resource relationship.
     *
     * @param  string|array  $relationship
     * @return static
     */
    public static function make($relationship)
    {
        return new self($relationship);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return (array) $this->relationship;
    }
}
