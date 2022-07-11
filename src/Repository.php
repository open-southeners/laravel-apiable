<?php

namespace OpenSoutheners\LaravelApiable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use OpenSoutheners\LaravelApiable\Http\ApplyFieldsToQuery;
use OpenSoutheners\LaravelApiable\Http\ApplyFiltersToQuery;
use OpenSoutheners\LaravelApiable\Http\ApplyIncludesToQuery;
use OpenSoutheners\LaravelApiable\Http\ApplySortsToQuery;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
class Repository
{
    /**
     * @var \Illuminate\Pipeline\Pipeline
     */
    protected $pipeline;

    /**
     * @var \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
     */
    protected $requestQueryObject;

    /**
     * @var class-string<\OpenSoutheners\LaravelApiable\Contracts\JsonApiable|\Illuminate\Database\Eloquent\Model>
     */
    protected $model;

    /**
     * @var bool
     */
    protected $includeAllowedToResponse = false;

    /**
     * Instantiate this class.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     */
    public function __construct($query, ?Request $request = null)
    {
        $this->pipeline = app(Pipeline::class);

        $query = $this->getQuery($query);

        $this->requestQueryObject = new RequestQueryObject($request ?: app(Request::class), $query);

        $this->model = $query->getModel();
    }

    /**
     * Create new instance of repository from query.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Builder  $query
     * @return static
     */
    public static function from($query)
    {
        return new static($query);
    }

    /**
     * Get query builder instance from whatever is sent.
     *
     * @param  mixed  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getQuery($query)
    {
        if (is_string($query) && method_exists($query, 'query')) {
            return $query::query();
        }

        if ($query instanceof Model) {
            return $query->newQuery();
        }

        return $query;
    }

    /**
     * Build pipeline and return resulting request query object instance.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
     */
    protected function buildPipeline()
    {
        return $this->pipeline->send($this->requestQueryObject)
            ->via('from')
            ->through([
                ApplyIncludesToQuery::class,
                ApplyFiltersToQuery::class,
                ApplyFieldsToQuery::class,
                ApplySortsToQuery::class,
            ])->thenReturn();
    }

    /**
     * Get query from request query object pipeline.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getPipelineQuery()
    {
        return $this->buildPipeline()->query;
    }

    public function includeAllowedToResponse($value = true)
    {
        $this->includeAllowedToResponse = $value;

        return $this;
    }

    /**
     * List resources from a query.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
     */
    public function list()
    {
        return $this->resultPostProcessing($this->getPipelineQuery()->jsonApiPaginate());
    }

    /**
     * Get all resources by column/value condition.
     *
     * @param  mixed  $column
     * @param  mixed  $value
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
     */
    public function getBy($column, $value)
    {
        return $this->getPipelineQuery()
            ->where($column, $value)
            ->jsonApiPaginate();
    }

    /**
     * Post-process result from query to apply appended attributes.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection  $result
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
     */
    protected function resultPostProcessing($result)
    {
        $allowedAppends = $this->requestQueryObject->getAllowedAppends();

        $filteredUserAppends = array_intersect_key(
            $this->requestQueryObject->fields(),
            $allowedAppends
        );

        foreach ($filteredUserAppends as $key => $values) {
            $filteredUserAppends[$key] = array_intersect($allowedAppends[$key], $values);
        }

        if (! empty($allowedAppends)) {
            // TODO: Not really optimised, need to think of a better solution...
            // TODO: Or refactor old "transformers" classes with a "plain tree" of resources
            $result->collection->each(function (JsonApiResource $item) use ($filteredUserAppends) {
                /** @var array<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource> $resourceIncluded */
                $resourceIncluded = $item->with['included'];

                if ($appendsArr = $filteredUserAppends[$item->resource->jsonApiableOptions()->resourceType] ?? null) {
                    $item->append($appendsArr);
                }

                foreach ($resourceIncluded as $included) {
                    if ($appendsArr = $filteredUserAppends[$included->resource->jsonApiableOptions()->resourceType] ?? null) {
                        $included->resource->append($appendsArr);
                    }
                }
            });
        }

        if ($this->includeAllowedToResponse) {
            $result->additional(['meta' => array_filter([
                'allowed_filters' => $this->requestQueryObject->getAllowedFilters(),
                'allowed_sorts' => $this->requestQueryObject->getAllowedSorts()
            ])]);
        }

        return $result;
    }

    public function __call($method, array $arguments)
    {
        call_user_func_array([$this->requestQueryObject, $method], $arguments);

        return $this;
    }
}
