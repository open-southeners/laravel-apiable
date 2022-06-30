<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

interface JsonApiable
{
    /**
     * Transform the model instance attributes to JSON:API.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource
     */
    public function toJsonApi();

    /**
     * Set options for model to be serialize with JSON:API.
     *
     * @return \OpenSoutheners\LaravelApiable\JsonApiableOptions
     */
    public function jsonApiableOptions();
}
