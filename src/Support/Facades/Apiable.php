<?php

namespace OpenSoutheners\LaravelApiable\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Support\Apiable
 */
class Apiable extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'apiable';
    }
}
