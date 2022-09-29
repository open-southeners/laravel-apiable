<?php

namespace OpenSoutheners\LaravelApiable\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use function OpenSoutheners\LaravelHelpers\Classes\class_use;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Apiable
{
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, string>
     */
    protected static $modelResourceTypeMap = [];

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
        return match (true) {
            ! is_object($resource) => false,
            $resource instanceof Collection, class_use($resource, \OpenSoutheners\LaravelApiable\Concerns\JsonApiable::class) => $resource->toJsonApi(),
            $resource instanceof Builder => $resource->jsonApiPaginate(),
            $resource instanceof LengthAwarePaginator => new JsonApiCollection($resource),
            $resource instanceof Model || $resource instanceof MissingValue => new JsonApiResource($resource),
            default => false,
        };
    }

    /**
     * Determine default resource type from giving model.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Model
     * @return string
     */
    public static function resourceTypeForModel($model)
    {
        return Str::snake(class_basename(is_string($model) ? $model : get_class($model)));
    }

    /**
     * Get resource type from a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return string
     */
    public static function getResourceType($model)
    {
        return static::$modelResourceTypeMap[is_string($model) ? $model : get_class($model)]
            ?? static::resourceTypeForModel($model);
    }

    /**
     * Transforms error rendering to a JSON:API complaint error response.
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonApiRenderable(Throwable $e, $request)
    {
        $response = ['errors' => []];

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($e instanceof HttpExceptionInterface || method_exists($e, 'getStatusCode')) {
            /** @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e */
            $statusCode = $e->getStatusCode();
        }

        if ($e instanceof ValidationException) {
            /** @var \Illuminate\Validation\ValidationException $e */
            $statusCode = 422;

            foreach ($e->errors() as $errorSource => $errors) {
                foreach ($errors as $error) {
                    $response['errors'][] = [
                        'code' => $statusCode,
                        'title' => $error,
                        'source' => [
                            'pointer' => $errorSource,
                        ],
                    ];
                }
            }
        }

        if ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
            $response['errors'][0]['code'] = $e->getCode() ?: $statusCode;
            $response['errors'][0]['title'] = $e->getMessage();
            $response['errors'][0]['trace'] = $e->getTrace();
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Prepare response allowing user requests from query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\OpenSoutheners\LaravelApiable\Contracts\JsonApiable|class-string<\OpenSoutheners\LaravelApiable\Contracts\JsonApiable>  $query
     * @param  array  $alloweds
     * @return \OpenSoutheners\LaravelApiable\Http\JsonApiResponse
     */
    public static function response($query, array $alloweds = [])
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
     * @param  string  $type
     * @return Model|false
     */
    public static function getModelFromResourceType(string $type)
    {
        return array_flip(static::$modelResourceTypeMap)[$type] ?? false;
    }

    /**
     * Add suffix to filter attribute/scope name.
     *
     * @param  string  $value
     * @return string
     */
    public static function scopedFilterSuffix(string $value)
    {
        return "${value}_scoped";
    }
}
