<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Traits\ForwardsCalls;
use OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder;
use OpenSoutheners\LaravelApiable\Contracts\ViewQueryable;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;
use function OpenSoutheners\LaravelHelpers\Classes\class_implement;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
class JsonApiResponse implements Responsable, Arrayable
{
    use Concerns\IteratesResultsAfterQuery;
    use Concerns\ResolvesFromRouteAction;
    use ForwardsCalls;

    /**
     * @var \Illuminate\Pipeline\Pipeline
     */
    protected $pipeline;

    /**
     * @var \OpenSoutheners\LaravelApiable\Http\RequestQueryObject|null
     */
    protected $request;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model;

    /**
     * @var bool|null
     */
    protected $includeAllowedToResponse = null;

    /**
     * @var bool
     */
    protected $singleResourceResponse = false;

    /**
     * @var array<string>
     */
    protected $forceAppends = [];

    /**
     * Instantiate this class.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     */
    public function __construct(?Request $request = null)
    {
        $this->request = new RequestQueryObject($request);

        $this->pipeline = app(Pipeline::class);

        $this->resolveFromRoute();
    }

    /**
     * Create new instance of repository from query.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Builder  $modelOrQuery
     * @return static
     */
    public static function from($modelOrQuery)
    {
        return (new static())->using($modelOrQuery);
    }

    /**
     * Use the specified model for this JSON:API response.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Builder  $modelOrQuery
     * @return \OpenSoutheners\LaravelApiable\Http\JsonApiResponse
     */
    public function using($modelOrQuery)
    {
        $this->model = is_string($modelOrQuery) ? $modelOrQuery : get_class($modelOrQuery->getModel());

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = is_string($modelOrQuery) ? $modelOrQuery::query() : clone $modelOrQuery;

        if (class_implement($this->model, ViewQueryable::class) || class_implement($query, ViewableBuilder::class)) {
            $query->viewable();
        }

        $this->request->setQuery($query);

        return $this;
    }

    /**
     * Build pipeline and return resulting request query object instance.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
     */
    protected function buildPipeline()
    {
        if (! $this->request->query) {
            throw new Exception('RequestQueryObject needs a base query to work, none provided');
        }

        return $this->pipeline->send($this->request)
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
     * Get single resource from response.
     *
     * @return $this
     */
    public function gettingOne()
    {
        $this->singleResourceResponse = true;

        return $this;
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
     * Get results from processing RequestQueryObject pipeline.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection|\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource
     */
    protected function getResults()
    {
        return $this->resultPostProcessing(
            Apiable::toJsonApi(
                $this->singleResourceResponse
                    ? $this->buildPipeline()->query->first()
                    : $this->buildPipeline()->query
            )
        );
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->hasMacro('inertia') && $request->inertia()) {
            return $this->toArray($request);
        }

        return $this->getResults()->toResponse($request);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string>
     */
    public function toArray()
    {
        $results = $this->getResults();

        if (! ($results instanceof JsonApiCollection)) {
            return $results->toArray($this->getRequest());
        }

        $responseArray = ['data' => $results->collection->map->toArray($this->getRequest())];

        foreach (array_filter($results->with) as $key => $value) {
            $responseArray[$key] = $value;
        }

        if (! empty($results->additional)) {
            $responseArray = array_merge_recursive($responseArray, $results->additional);
        }

        return $responseArray;
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

            $type = $this->model;
        }

        $resourceType = class_exists($type) ? Apiable::getResourceType($type) : $type;

        $this->forceAppends = array_merge_recursive($this->forceAppends, [$resourceType => $attributes]);

        return $this;
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
        return $this->forwardDecoratedCallTo($this->request, $name, $arguments);
    }
}
