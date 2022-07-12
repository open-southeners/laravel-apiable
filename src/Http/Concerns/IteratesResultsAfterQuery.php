<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\JsonApiResponse
 */
trait IteratesResultsAfterQuery
{
    /**
     * Add allowed user appends to result.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection  $result
     * @return void
     */
    public function addAppendsToResult($result)
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
            $result->collection->each(function (JsonApiResource $item) use ($filteredUserAppends) {
                /** @var array<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource> $resourceIncluded */
                $resourceIncluded = $item->with['included'] ?? [];

                if ($appendsArr = $filteredUserAppends[$item->resource->jsonApiableOptions()->resourceType] ?? null) {
                    $item->makeVisible($appendsArr)->append($appendsArr);
                }

                foreach ($resourceIncluded as $included) {
                    if ($appendsArr = $filteredUserAppends[$included->resource->jsonApiableOptions()->resourceType] ?? null) {
                        $included->resource->makeVisible($appendsArr)->append($appendsArr);
                    }
                }
            });
        }
    }
}
