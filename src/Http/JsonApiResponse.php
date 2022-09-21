<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Traits\ForwardsCalls;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;
use function OpenSoutheners\LaravelHelpers\Models\key_from;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
class JsonApiResponse
{
    use Concerns\IteratesResultsAfterQuery;
    use ForwardsCalls;

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
     * @var bool|null
     */
    protected $includeAllowedToResponse = null;

    /**
     * @var array<string>
     */
    protected $forceAppends = [];

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
     * Get class string from model.
     *
     * @return string
     */
    protected function getModelClass()
    {
        return is_string($this->model) ? $this->model : get_class($this->model);
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
                ApplyFulltextSearchToQuery::class,
                ApplyFiltersToQuery::class,
                ApplyIncludesToQuery::class,
                ApplyFieldsToQuery::class,
                ApplySortsToQuery::class,
            ])->thenReturn();
    }

    /**
     * Get query from request query object pipeline.
     *
     * @param  \Closure|null  $callback
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource|\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
     */
    public function getPipelineQuery($callback = null)
    {
        $pipelineQuery = $this->buildPipeline()->query;

        return $this->resultPostProcessing($callback ? $callback($pipelineQuery) : $pipelineQuery);
    }

    /**
     * Add allowed filters and sorts to the response meta.
     *
     * @param  bool|null  $value
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
        return $this->getPipelineQuery(fn (Builder $query) => $query->jsonApiPaginate());
    }

    /**
     * Get a single resource from a request query object.
     *
     * @param  \OpenSoutheners\LaravelApiable\Contracts\JsonApiable|int|string  $key
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource
     */
    public function getOne($key)
    {
        return $this->getPipelineQuery(fn (Builder $query) => $query
            ->whereKey(key_from($key))
            ->first()
            ->toJsonApi()
        );
    }

    /**
     * Force append attributes to be included without being allowed.
     *
     * @param  string|array|class-string<\Illuminate\Database\Eloquent\Model>  $type
     * @param  array  $attributes
     * @return JsonApiResponse
     */
    public function forceAppend($type, array $attributes = [])
    {
        if (is_array($type)) {
            $attributes = $type;

            $type = $this->getModelClass($this->model);
        }

        $resourceType = class_exists($type) ? Apiable::getResourceType($type) : $type;

        $this->forceAppends = array_merge_recursive($this->forceAppends, [$resourceType => $attributes]);

        return $this;
    }

    /**
     * Post-process result from query to apply appended attributes.
     * TODO: This should be in a new class/trait!
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection|\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource  $result
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection|\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource
     */
    protected function resultPostProcessing($result)
    {
        $this->addAppendsToResult($result);

        $includeAllowed = is_null($this->includeAllowedToResponse)
            ? Apiable::config('responses.include_allowed')
            : $this->includeAllowedToResponse;

        if ($includeAllowed) {
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
        return $this->forwardDecoratedCallTo($this->requestQueryObject, $name, $arguments);
    }
}
