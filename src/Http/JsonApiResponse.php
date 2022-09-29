<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Traits\ForwardsCalls;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;
use function OpenSoutheners\LaravelHelpers\Models\key_from;
use Illuminate\Contracts\Support\Responsable;
use ReflectionClass;
use ReflectionAttribute;
use OpenSoutheners\LaravelApiable\Attributes\QueryParam;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Exception;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
class JsonApiResponse implements Responsable
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
     * @param  class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Builder $modelOrQuery
     * @return static
     */
    public static function from($modelOrQuery)
    {
        $instance = new static();

        return $instance->using($modelOrQuery);
    }

    /**
     * Use the specified model for this JSON:API response.
     * 
     * @param class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Builder $modelOrQuery
     * @return \OpenSoutheners\LaravelApiable\Http\JsonApiResponse
     */
    public function using($modelOrQuery)
    {
        if (is_string($modelOrQuery)) {
            $this->model = $modelOrQuery;
        } else {
            $this->model = $modelOrQuery->getModel();

            $this->request->setQuery($modelOrQuery);
        }

        return $this;
    }

    /**
     * Get query builder instance from whatever is sent.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function queryFromModel()
    {
        if (! $this->model) {
            throw new Exception('Model is required for JsonApiResponse to be resolved as response.');
        }

        return $this->model::query();
    }

    /**
     * Build pipeline and return resulting request query object instance.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
     */
    protected function buildPipeline()
    {
        if (! $this->request->query) {
            $this->request->setQuery($this->queryFromModel());
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
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return $this->resultPostProcessing(
            Apiable::toJsonApi(
                $this->singleResourceResponse
                    ? $this->buildPipeline()->query->first()
                    : $this->buildPipeline()->query
            )
        )->toResponse($request);
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
