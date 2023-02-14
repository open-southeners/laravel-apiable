<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

interface JsonApiable
{
    /**
     * Transform the model instance attributes to JSON:API.
     */
    public function toJsonApi(): JsonApiResource;
}
