<?php

namespace OpenSoutheners\LaravelApiable\Concerns;

use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

/**
 * @phpstan-require-implements JsonApiable
 */
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
