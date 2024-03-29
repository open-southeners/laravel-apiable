<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class SearchFilterQueryParam extends QueryParam
{
    public function __construct(public string $attribute, public $values = '*')
    {
        //
    }
}
