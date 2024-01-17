<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

class ResourceResponse
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $resource
     */
    public function __construct(public string $resource)
    {
        //
    }
}
