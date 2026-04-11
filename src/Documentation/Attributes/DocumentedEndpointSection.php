<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Attributes;

use Attribute;

/**
 * Marks a controller method as a documented API endpoint section.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class DocumentedEndpointSection
{
    public function __construct(
        public readonly string $title = '',
        public readonly string $description = '',
    ) {
        //
    }
}
