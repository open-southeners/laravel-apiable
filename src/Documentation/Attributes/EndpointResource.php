<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Attributes;

use Attribute;

/**
 * Binds a controller to an Eloquent model for example payload generation.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EndpointResource
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $resource  Fully-qualified Eloquent model class name.
     */
    public function __construct(
        public readonly string $resource,
    ) {
        //
    }
}
