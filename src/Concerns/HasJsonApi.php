<?php

namespace OpenSoutheners\LaravelApiable\Concerns;

use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

trait HasJsonApi
{
    /**
     * Transform the model instance attributes to JSON:API.
     */
    public function toJsonApi(): JsonApiResource
    {
        return new JsonApiResource($this);
    }
}
