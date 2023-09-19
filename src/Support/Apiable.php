<?php

namespace OpenSoutheners\LaravelApiable\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Handler;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use Throwable;

class Apiable
{
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, string>
     */
    protected static $modelResourceTypeMap = [];

    /**
     * Get package prefixed config by key.
     */
    public static function config(string $key): mixed
    {
        return config("apiable.$key");
    }

    /**
     * Format model or collection of models to JSON:API, false otherwise if not valid resource.
     */
    public static function toJsonApi(mixed $resource): JsonApiResource|JsonApiCollection
    {
        return match (true) {
            ! is_object($resource) => new JsonApiCollection(Collection::make([])),
            $resource instanceof Collection, $resource instanceof JsonApiable => $resource->toJsonApi(),
            $resource instanceof Builder => $resource->jsonApiPaginate(),
            $resource instanceof AbstractPaginator => new JsonApiCollection($resource),
            $resource instanceof Model, $resource instanceof MissingValue => new JsonApiResource($resource),
            default => new JsonApiCollection(Collection::make([])),
        };
    }

    /**
     * Determine default resource type from giving model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public static function resourceTypeForModel(Model|string $model): string
    {
        return Str::snake(class_basename(is_string($model) ? $model : get_class($model)));
    }

    /**
     * Get resource type from a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public static function getResourceType(Model|string $model): string
    {
        return static::$modelResourceTypeMap[is_string($model) ? $model : get_class($model)]
            ?? static::resourceTypeForModel($model);
    }

    /**
     * Transforms error rendering to a JSON:API complaint error response.
     */
    public static function jsonApiRenderable(Throwable $e, bool $withTrace = null): Handler
    {
        return new Handler($e, $withTrace);
    }

    /**
     * Prepare response allowing user requests from query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\OpenSoutheners\LaravelApiable\Contracts\JsonApiable|class-string<\OpenSoutheners\LaravelApiable\Contracts\JsonApiable>  $query
     */
    public static function response($query, array $alloweds = []): JsonApiResponse
    {
        $response = JsonApiResponse::from($query);

        if (! empty($alloweds)) {
            return $response->allowing($alloweds);
        }

        return $response;
    }

    /**
     * Add models to JSON:API types mapping to the application.
     *
     * @param  array<class-string<\Illuminate\Database\Eloquent\Model>>|array<class-string<\Illuminate\Database\Eloquent\Model>, string>  $models
     * @return void
     */
    public static function modelResourceTypeMap(array $models = [])
    {
        if (! Arr::isAssoc($models)) {
            $models = array_map(fn ($model) => static::resourceTypeForModel($model), $models);
        }

        static::$modelResourceTypeMap = $models;
    }

    /**
     * Get models to JSON:API types mapping.
     *
     * @return array<class-string<\Illuminate\Database\Eloquent\Model>, string>
     */
    public static function getModelResourceTypeMap()
    {
        return static::$modelResourceTypeMap;
    }

    /**
     * Get model class from
     *
     * @return \Illuminate\Database\Eloquent\Model|false
     */
    public static function getModelFromResourceType(string $type)
    {
        return array_flip(static::$modelResourceTypeMap)[$type] ?? false;
    }

    /**
     * Add suffix to filter attribute/scope name.
     *
     * @return string
     */
    public static function scopedFilterSuffix(string $value)
    {
        return "{$value}_scoped";
    }

    /**
     * Force responses to be formatted in a specific format type.
     * 
     * @param string|null $format
     * @return void
     */
    public static function forceResponseFormatting(string|null $format = null)
    {
        config(['apiable.responses.formatting.force' => true]);

        if ($format) {
            config(['apiable.responses.formatting.type' => $format]);
        }
    }
}
