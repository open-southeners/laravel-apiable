<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;

class AllowedFields implements Arrayable
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $attributes;

    /**
     * Make an instance of this class.
     *
     * @param  string  $type
     * @param  string|array<string>  $attributes
     * @return void
     */
    public function __construct(string $type, $attributes)
    {
        $this->type = $type;
        $this->attributes = [$attributes];
    }

    /**
     * Allow include fields (attributes) to resource type.
     *
     * @param  string  $type
     * @param  string|array<string>  $attributes
     * @return static
     */
    public static function make(string $type, $attributes)
    {
        return new static($type, $attributes);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, array<string>>
     */
    public function toArray()
    {
        return [
            $this->type => $this->attributes,
        ];
    }
}
