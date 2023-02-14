<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

class AllowedFields implements Arrayable
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var array<string|array<string>>
     */
    protected $attributes;

    /**
     * Make an instance of this class.
     *
     * @param  string|array<string>  $attributes
     * @return void
     */
    public function __construct(string $type, $attributes)
    {
        $this->type = class_exists($type) ? Apiable::getResourceType($type) : $type;
        $this->attributes = (array) $attributes;
    }

    /**
     * Allow include fields (attributes) to resource type.
     *
     * @param  string|array<string>  $attributes
     */
    public static function make(string $type, $attributes): static
    {
        return new static($type, $attributes);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, array<string>>
     */
    public function toArray(): array
    {
        return [
            $this->type => is_array(head($this->attributes))
                ? array_merge(...$this->attributes)
                : $this->attributes,
        ];
    }
}
