<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class FieldsQueryParam extends QueryParam
{
    public function __construct(public string $type, public array $fields, public string $description = '')
    {
        //
    }
}
