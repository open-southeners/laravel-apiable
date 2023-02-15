<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Traits\ForwardsCalls;
use OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder;
use OpenSoutheners\LaravelApiable\Contracts\ViewQueryable;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
class JsonApiResponse implements Responsable, Arrayable
{
    use Concerns\IteratesResultsAfterQuery;
    use Concerns\ResolvesFromRouteAction;
    use ForwardsCalls;

    protected Pipeline $pipeline;

    protected RequestQueryObject|null $request;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Model>|class-string<\OpenSoutheners\LaravelApiable\Contracts\ViewQueryable>
     */
    protected string $model;

    protected bool|null $includeAllowedToResponse = null;

    protected bool $singleResourceResponse = false;

    /**
     * @var array<string>
     */
    protected array $forceAppends = [];

    /**
     * Instantiate this class.
     *
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
     */
    public static function from($modelOrQuery): static
    {
        return (new static())->using($modelOrQuery);
    }

    /**
     * Use the specified model for this JSON:API response.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Builder  $modelOrQuery
     */
    public function using($modelOrQuery): static
    {
        $this->model = is_string($modelOrQuery) ? $modelOrQuery : get_class($modelOrQuery->getModel());

        /** @var \Illuminate\Database\Eloquent\Builder|\OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder $query */
        $query = is_string($modelOrQuery) ? $modelOrQuery::query() : clone $modelOrQuery;

        if (
            is_a($this->model, ViewQueryable::class, true)
            || is_a($query, ViewableBuilder::class)
        ) {
            $query->viewable();
        }

        $this->request->setQuery($query);

        return $this;
    }

    /**
     * Build pipeline and return resulting request query object instance.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
     *
     * @throws \Exception
     */
    protected function buildPipeline()
    {
        if (! $this->request?->query) {
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
     */
    public function gettingOne(): static
    {
        $this->singleResourceResponse = true;

        return $this;
    }

    /**
     * Add allowed filters and sorts to the response meta.
     */
    public function includeAllowedToResponse(bool|null $value = true): static
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
     */
    public function toResponse($request): mixed
    {
        $response = $this->getResults()->toResponse($request);

        if ($request->hasMacro('inertia') && method_exists($request, 'inertia') && $request->inertia()) {
            return $response->getData();
        }

        return $response;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        $response = $this->toResponse(app(Request::class));

        if ($response instanceof Response && method_exists($response, 'getData')) {
            return (array) $response->getData();
        }

        return (array) $response;
    }

    /**
     * Force append attributes to be included without being allowed.
     *
     * @param  string|array|class-string<\Illuminate\Database\Eloquent\Model>  $type
     */
    public function forceAppend(string|array $type, array $attributes = []): static
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
     * Force append attributes to be included without being allowed only when condition matches.
     *
     * @param  string|array|class-string<\Illuminate\Database\Eloquent\Model>  $type
     */
    public function forceAppendWhen(Closure|bool $condition, string|array $type, array $attributes = []): static
    {
        if (is_callable($condition)) {
            $condition = $condition();
        }

        if (! $condition) {
            return $this;
        }

        return $this->forceAppend($type, $attributes);
    }

    /**
     * Set response to include IDs on resource attributes.
     */
    public function includingIdAttributes(bool $value = true): static
    {
        config(['apiable.responses.include_ids_on_attributes' => $value]);

        return $this;
    }

    /**
     * Conditionally query results to display based on viewable query (if available).
     */
    public function conditionallyLoadResults(bool $value = true): self
    {
        config(['apiable.responses.viewable' => $value]);

        return $this;
    }

    /**
     * Call method of RequestQueryObject if not exists on this.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->forwardDecoratedCallTo($this->request, $name, $arguments);
    }
}
