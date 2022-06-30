<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @property \Illuminate\Support\Collection $collection
 */
trait CollectsWithIncludes
{
    /**
     * Attach with the collects' resource models relationships.
     *
     * @return void
     */
    protected function withIncludes()
    {
        $collectionIncludes = Collection::make(
            $this->with['include'] ?? []
        );

        /** @var \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource $jsonResource */
        foreach ($this->collection->toArray() as $jsonResource) {
            /** @var \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource $resource */
            foreach ($jsonResource->getIncluded() as $resource) {
                $collectionIncludes->push($resource);
            }
        }

        $includesArr = $this->checkUniqueness(
            $collectionIncludes
        )->values()->all();

        if (! empty($includesArr)) {
            Arr::set($this->with, 'include', $includesArr);
        }
    }
}
