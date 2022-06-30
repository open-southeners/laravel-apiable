<?php

namespace OpenSoutheners\LaravelApiable;

use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use function OpenSoutheners\LaravelHelpers\Classes\class_use;

/**
 * @mixin \Illuminate\Support\Collection
 */
class Collection
{
    public function toJsonApi()
    {
        return function () {
            return new JsonApiCollection(
                $this->filter(function ($item) {
                    return is_object($item) && class_use($item, \OpenSoutheners\LaravelApiable\Concerns\Apiable::class, true);
                })
            );
        };
    }
}
