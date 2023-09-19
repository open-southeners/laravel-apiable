<?php

namespace OpenSoutheners\LaravelApiable\Http;

/**
 * @mixin \Illuminate\Http\Request
 */
class Request
{
    public function wantsJsonApi()
    {
        return function () {
            return $this->header('Accept') === 'application/vnd.api+json';
        };
    }
}
