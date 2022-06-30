<?php

namespace OpenSoutheners\LaravelApiable\Testing;

use Closure;

class TestResponseMacros
{
    public function assertJsonApi()
    {
        return function (Closure $callback = null) {
            $assert = AssertableJsonApi::fromTestResponse($this);

            if ($callback === null) {
                return $this;
            }

            $callback($assert);

            return $this;
        };
    }
}
