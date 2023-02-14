<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;
use OpenSoutheners\LaravelApiable\Http\QueryParamsValidator;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\Support\Apiable;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\JsonApiResponse
 */
trait IteratesResultsAfterQuery
{
    /**
     * Post-process result from query to apply appended attributes.
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
                'allowed_filters' => $this->request->getAllowedFilters(),
                'allowed_sorts' => $this->request->getAllowedSorts(),
            ])]);
        }

        if ($result instanceof JsonApiCollection) {
            $result->withQuery(
                array_filter(
                    $this->getRequest()->query->all(),
                    fn ($queryParam) => $queryParam !== 'page',
                    ARRAY_FILTER_USE_KEY
                )
            );
        }

        return $result;
    }

    /**
     * Add allowed user appends to result.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection|\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource  $result
     * @return void
     */
    protected function addAppendsToResult($result)
    {
        $filteredUserAppends = (new QueryParamsValidator(
            $this->request->appends(),
            $this->request->enforcesValidation(),
            $this->request->getAllowedAppends()
        ))->when(
            function ($key, $modifiers, $values, $rules, &$valids) {
                $valids = array_intersect($values, $rules);

                return empty(array_diff($values, $rules));
            },
            fn ($key, $values) => throw new Exception(sprintf('"%s" fields for resource type "%s" cannot be appended', implode(', ', $values), $key))
        )->validate();

        // This are forced by the application owner / developer...
        // So the values are bypassing allowed appends
        if (! empty($this->forceAppends)) {
            $filteredUserAppends = array_merge_recursive($filteredUserAppends, $this->forceAppends);
        }

        if (! empty($filteredUserAppends)) {
            // TODO: Not really optimised, need to think of a better solution...
            // TODO: Or refactor old "transformers" classes with a "plain tree" of resources
            $result instanceof JsonApiCollection
                ? $result->collection->each(fn (JsonApiResource $item) => $this->appendToApiResource($item, $filteredUserAppends))
                : $this->appendToApiResource($result, $filteredUserAppends);
        }
    }

    /**
     * Append array of attributes to the resulted JSON:API resource.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource|mixed  $resource
     * @return void
     */
    protected function appendToApiResource(mixed $resource, array $appends)
    {
        if (! ($resource instanceof JsonApiResource)) {
            return;
        }

        /** @var array<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource> $resourceIncluded */
        $resourceIncluded = $resource->with['included'] ?? [];
        $resourceType = Apiable::getResourceType($resource->resource);

        if ($appendsArr = $appends[$resourceType] ?? null) {
            $resource->resource->makeVisible($appendsArr)->append($appendsArr);
        }

        foreach ($resourceIncluded as $included) {
            $includedResourceType = Apiable::getResourceType($included->resource);

            if ($appendsArr = $appends[$includedResourceType] ?? null) {
                $included->resource->makeVisible($appendsArr)->append($appendsArr);
            }
        }
    }
}
