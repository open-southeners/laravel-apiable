<?php

namespace OpenSoutheners\LaravelApiable\Enums;

enum QueryParamValueType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Number = 'number';
    case Boolean = 'boolean';
    case Array = 'array';
    case Enum = 'enum';
    case Date = 'date';
    case DateTime = 'datetime';
}
