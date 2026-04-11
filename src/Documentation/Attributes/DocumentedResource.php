<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Attributes;

use Attribute;

/**
 * Marks a controller class as a documented API resource group.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DocumentedResource
{
    public function __construct(
        public readonly string $name,
        public readonly string $description = '',
        public readonly string $prefix = '',
    ) {
        //
    }
}
