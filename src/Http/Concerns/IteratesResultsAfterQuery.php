<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\JsonApiResponse
 */
trait IteratesResultsAfterQuery
{
    /**
     * Add allowed user appends to result.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection|\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource  $result
     * @return void
     */
    protected function addAppendsToResult($result)
    {
        $allowedAppends = $this->requestQueryObject->getAllowedAppends();

        $filteredUserAppends = array_intersect_key(
            $this->requestQueryObject->appends(),
            $allowedAppends
        );

        foreach ($filteredUserAppends as $key => $values) {
            $filteredUserAppends[$key] = array_intersect($allowedAppends[$key], $values);
        }

        if (! empty($allowedAppends)) {
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
     * @param  JsonApiResource  $resource
     * @param array appends
     * @return void
     */
    protected function appendToApiResource(JsonApiResource $resource, array $appends)
    {
        /** @var array<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource> $resourceIncluded */
        $resourceIncluded = $resource->with['included'] ?? [];

        if ($appendsArr = $appends[$resource->resource->jsonApiableOptions()->resourceType] ?? null) {
            $resource->makeVisible($appendsArr)->append($appendsArr);
        }

        foreach ($resourceIncluded as $included) {
            if ($appendsArr = $appends[$included->resource->jsonApiableOptions()->resourceType] ?? null) {
                $included->resource->makeVisible($appendsArr)->append($appendsArr);
            }
        }
    }
}
