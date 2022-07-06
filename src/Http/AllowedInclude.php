<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Stringable;

class AllowedInclude implements Stringable
{
    /**
     * @var string
     */
    protected $relationship;

    /**
     * Make an instance of this class.
     *
     * @param  string  $relationship
     * @return void
     */
    public function __construct($relationship)
    {
        $this->relationship = $relationship;
    }

    /**
     * Allow include resource relationship.
     *
     * @param  string  $relationship
     * @return static
     */
    public static function make($relationship)
    {
        return new self($relationship);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->relationship;
    }
}
