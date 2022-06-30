<?php

namespace OpenSoutheners\LaravelApiable\Http;

/**
 * @mixin \Illuminate\Http\Request
 */
class Request
{
    public const JSON_API_HEADER = 'application/vnd.api+json';

    public function wantsJsonApi()
    {
        return function () {
            return $this->header('Content-Type', $this->header('Accept')) === Request::JSON_API_HEADER;
        };
    }
}
