<?php

namespace OpenSoutheners\LaravelApiable\Http;

class AllowedAppends extends AllowedFields
{
    /**
     * Allow include attributes (as an append or accessor) to resource type.
     *
     * @param  string|array<string>  $attributes
     */
    public static function make(string $type, string|array $attributes): self
    {
        return new self($type, $attributes);
    }
}
