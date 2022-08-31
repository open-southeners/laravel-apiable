<?php

namespace OpenSoutheners\LaravelApiable\Concerns;

use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

trait HasJsonApi
{
    /**
     * Transform the model instance attributes to JSON:API.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource
     */
    public function toJsonApi()
    {
        return new JsonApiResource($this);
    }
}
