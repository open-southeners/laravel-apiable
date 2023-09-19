<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ApplyDefaultFilter
{
    public function __construct(
        public string $attribute,
        public int|array|null $operator = null,
        public string|array $values = ''
    ) {
        // 
    }
}
