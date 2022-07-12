<?php

namespace OpenSoutheners\LaravelApiable\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\JsonApiResponse;
use function OpenSoutheners\LaravelHelpers\Classes\class_use;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

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

        if ($resource instanceof Builder) {
            return $resource->jsonApiPaginate();
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
     * Transform attribute to a camelCase (method/scope standarised).
     *
     * @param  string  $value
     * @return string
     */
    public static function attributeToScope(string $value)
    {
        return lcfirst(
            implode(
                array_map(fn ($word) => ucfirst($word), explode(' ', str_replace(['-', '_'], ' ', $value)))
            )
        );
    }

    /**
     * Prepare response allowing user requests from query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\OpenSoutheners\LaravelApiable\Contracts\JsonApiable  $query
     * @return \OpenSoutheners\LaravelApiable\JsonApiResponse|\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
     */
    public static function response($query, array $alloweds = [])
    {
        $response = JsonApiResponse::from($query);

        if (! empty($alloweds)) {
            return $response->allowing($alloweds)->list();
        }

        return $response;
    }
}
