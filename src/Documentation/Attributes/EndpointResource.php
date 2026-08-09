<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Binds a controller to an Eloquent model for example payload generation.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EndpointResource
{
    /**
     * @param  class-string<Model>  $resource  Fully-qualified Eloquent model class name.
     */
    public function __construct(
        public readonly string $resource,
    ) {
        //
    }
}
