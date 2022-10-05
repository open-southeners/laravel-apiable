<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AppendsQueryParam extends QueryParam
{
    public function __construct(public string $type, public array $attributes)
    {
        //
    }
}
