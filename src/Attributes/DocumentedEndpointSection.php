<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class DocumentedEndpointSection
{
    public function __construct(public string|null $title = null, public string $description = '')
    {
        //
    }
}
