<?php

namespace OpenSoutheners\LaravelApiable;

use Illuminate\Contracts\Support\Arrayable;

abstract class VersionedConfig implements Arrayable
{
    public function toArray(): array
    {
        $reflector = new \ReflectionClass($this);
        $configValues = [];

        /** @var \ReflectionProperty $property */
        foreach ($reflector->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $configValues[$property->getName()] = $property->getValue($this);
        }

        return [];
    }
}
