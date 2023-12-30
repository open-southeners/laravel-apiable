<?php

use OpenSoutheners\LaravelApiable\Support\Apiable;

if (! function_exists('apiable')) {
    function apiable(): Apiable
    {
        return app('apiable');
    }
}
