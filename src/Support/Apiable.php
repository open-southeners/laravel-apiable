<?php

namespace OpenSoutheners\LaravelApiable\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use function OpenSoutheners\LaravelHelpers\Classes\class_use;

class Apiable
{
    /**
     * Get package prefixed config by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public static function config(string $key)
    {
        return config("apiable.$key");
    }

    /**
     * Format model or collection of models to JSON:API, false otherwise if not valid resource.
     *
     * @param  mixed  $resource
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource|\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection|false
     */
    public static function toJsonApi($resource)
    {
        if (class_use($resource, \OpenSoutheners\LaravelApiable\Concerns\JsonApiable::class)) {
            return $resource->toJsonApi();
        }

        if ($resource instanceof Collection || $resource instanceof LengthAwarePaginator) {
            return new JsonApiCollection($resource);
        }

        if ($resource instanceof Model || $resource instanceof MissingValue) {
            return new JsonApiResource($resource);
        }

        return false;
    }

    /**
     * Determine default resource type from giving model.
     *
     * @param string|class-string|\Illuminate\Database\Eloquent\Model
     * @return string
     */
    public static function resourceTypeForModel($model)
    {
        return Str::snake(class_basename(is_string($model) ? $model : get_class($model)));
    }

    /**
     * Get resource type from a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string  $model
     * @return string
     */
    public static function getResourceType($model)
    {
        if ($model instanceof JsonApiable) {
            return $model->jsonApiableOptions()->resourceType;
        }

        return static::resourceTypeForModel($model);
    }
}
