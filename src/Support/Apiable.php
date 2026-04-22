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
use OpenSoutheners\LaravelApiable\Http\JsonApiPaginator;
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
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, class-string<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource>>
     */
    protected static $modelResourceMap = [];

    /**
     * Get package prefixed config by key.
     */
    public static function config(string $key): mixed
    {
        return config("apiable.$key");
    }

    /**
     * Format model or collection of models to JSON:API, false otherwise if not valid resource.
     *
     * @param  class-string<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource>|null  $resourceClass
     */
    public static function toJsonApi(mixed $resource, ?string $resourceClass = null): JsonApiResource|JsonApiCollection
    {
        return match (true) {
            $resource instanceof Builder => JsonApiPaginator::paginate($resource, resourceClass: $resourceClass),
            $resource instanceof AbstractPaginator, $resource instanceof Collection => new JsonApiCollection($resource, $resourceClass),
            $resource instanceof Model, $resource instanceof MissingValue => new ($resourceClass ?? static::jsonApiResourceFor($resource))($resource),
            default => new JsonApiCollection(Collection::make([])),
        };
    }

    /**
     * Get JSON:API resource class for a given model instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return class-string<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource>
     */
    public static function jsonApiResourceFor(Model $model): string
    {
        return static::$modelResourceMap[get_class($model)] ?? JsonApiResource::class;
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
    public static function jsonApiRenderable(Throwable $e, ?bool $withTrace = null): Handler
    {
        return new Handler($e, $withTrace);
    }

    /**
     * Prepare response allowing user requests from query.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<T>|T|class-string<T>  $query
     * @return \OpenSoutheners\LaravelApiable\Http\JsonApiResponse<T>
     */
    public static function response($query, array $alloweds = []): JsonApiResponse
    {
        $response = JsonApiResponse::from($query);

        if (! empty($alloweds)) {
            $response->allowing($alloweds);
        }

        return $response;
    }

    /**
     * Add models to JSON:API resource class mapping.
     *
     * @param  array<class-string<\Illuminate\Database\Eloquent\Model>, class-string<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource>>  $models
     * @return void
     */
    public static function modelResourceMap(array $models = [])
    {
        static::$modelResourceMap = $models;
    }

    /**
     * Get models to JSON:API resource class mapping.
     *
     * @return array<class-string<\Illuminate\Database\Eloquent\Model>, class-string<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource>>
     */
    public static function getModelResourceMap(): array
    {
        return static::$modelResourceMap;
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
     * Get model class from given resource type.
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
     * @return void
     */
    public static function forceResponseFormatting(?string $format = null)
    {
        config(['apiable.responses.formatting.force' => true]);

        if ($format) {
            config(['apiable.responses.formatting.type' => $format]);
        }
    }
}
