<?php

namespace OpenSoutheners\LaravelApiable;

use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;

/**
 * @mixin \Illuminate\Support\Collection
 */
class Collection
{
    public function toJsonApi()
    {
        return function (): JsonApiCollection {
            return new JsonApiCollection(
                $this->filter(function ($item) {
                    return is_object($item) && $item instanceof JsonApiable;
                })
            );
        };
    }
}
