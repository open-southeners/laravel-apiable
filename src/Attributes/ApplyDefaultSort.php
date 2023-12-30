<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ApplyDefaultSort
{
    public function __construct(
        public string $attribute,
        public ?int $direction = null
    ) {
        //
    }
}
