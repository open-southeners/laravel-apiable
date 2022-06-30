<?php

namespace OpenSoutheners\LaravelApiable\Concerns;

/**
 * @mixin \OpenSoutheners\LaravelApiable\JsonApiableOptions
 */
trait HasJsonApi
{
    /**
     * Transform the model instance attributes to JSON:API.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource
     */
    public function toJsonApi()
    {
        $transformerClass = $this->jsonApiableOptions()->transformer;

        return new $transformerClass($this);
    }
}
