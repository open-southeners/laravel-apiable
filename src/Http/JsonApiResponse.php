<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
class JsonApiResponse
{
    use Concerns\IteratesResultsAfterQuery;

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
                ApplyFiltersToQuery::class,
                ApplyIncludesToQuery::class,
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

    /**
     * Add allowed filters and sorts to the response meta.
     *
     * @param  bool  $value
     * @return $this
     */
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
        return $this->resultPostProcessing(
            $this->getPipelineQuery()->jsonApiPaginate()
        );
    }

    /**
     * Post-process result from query to apply appended attributes.
     * TODO: This should be in a new class/trait!
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection  $result
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
     */
    protected function resultPostProcessing($result)
    {
        $this->addAppendsToResult($result);

        if ($this->includeAllowedToResponse) {
            $result->additional(['meta' => array_filter([
                'allowed_filters' => $this->requestQueryObject->getAllowedFilters(),
                'allowed_sorts' => $this->requestQueryObject->getAllowedSorts(),
            ])]);
        }

        return $result;
    }

    /**
     * Call method of RequestQueryObject if not exists on this.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return $this
     */
    public function __call(string $name, array $arguments)
    {
        call_user_func_array([$this->requestQueryObject, $name], $arguments);

        return $this;
    }
}
