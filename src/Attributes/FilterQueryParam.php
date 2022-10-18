<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class FilterQueryParam extends QueryParam
{
    public function __construct(public string $attribute, public int|array|null $type = null, public $values = '*')
    {
        //
    }
}
