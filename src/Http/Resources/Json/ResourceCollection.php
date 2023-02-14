<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources\Json;

use Countable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\PaginatedResourceResponse;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use IteratorAggregate;
use OpenSoutheners\LaravelApiable\Http\Resources\CollectsResources;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

/**
 * @template T
 *
 * @extends JsonApiResource<\Illuminate\Support\Collection<T>|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Pagination\AbstractCursorPaginator>
 */
class ResourceCollection extends JsonApiResource implements Countable, IteratorAggregate
{
    use CollectsResources;

    /**
     * The resource that this resource collects.
     *
     * @var class-string<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource>
     */
    public $collects;

    /**
     * The mapped collection instance.
     *
     * @var \Illuminate\Support\Collection
     */
    public $collection;

    /**
     * Indicates if all existing request query parameters should be added to pagination links.
     */
    protected bool $preserveQueryParameters = false;

    /**
     * The query parameters that should be added to the pagination links.
     *
     * @var array|null
     */
    protected $queryParameters;

    /**
     * Create a new resource instance.
     */
    public function __construct(mixed $resource)
    {
        $this->resource = $this->collectResource($resource);
    }

    /**
     * Indicate that all current query parameters should be appended to pagination links.
     */
    public function preserveQuery(): self
    {
        $this->preserveQueryParameters = true;

        return $this;
    }

    /**
     * Specify the query string parameters that should be present on pagination links.
     */
    public function withQuery(array $query): self
    {
        $this->preserveQueryParameters = false;

        $this->queryParameters = $query;

        return $this;
    }

    /**
     * Return the count of items in the resource collection.
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): mixed
    {
        return $this->collection->map->toArray($request);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request): JsonResponse
    {
        if ($this->resource instanceof AbstractPaginator || $this->resource instanceof AbstractCursorPaginator) {
            return $this->preparePaginatedResponse($request);
        }

        return parent::toResponse($request);
    }

    /**
     * Create a paginate-aware HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    protected function preparePaginatedResponse($request): JsonResponse
    {
        if ($this->preserveQueryParameters) {
            $this->resource->appends($request->query());
        } elseif ($this->queryParameters !== null) {
            $this->resource->appends($this->queryParameters);
        }

        return (new PaginatedResourceResponse($this))->toResponse($request);
    }
}
