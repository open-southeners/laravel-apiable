<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

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
        $seen = [];
        $collectionIncludes = [];

        foreach ($this->with['included'] ?? [] as $resource) {
            $key = $this->getResourceKey($resource);

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $collectionIncludes[] = $resource;
            }
        }

        /** @var \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource $jsonResource */
        foreach ($this->collection as $jsonResource) {
            /** @var \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource $resource */
            foreach ($jsonResource->getIncluded() as $resource) {
                $key = $this->getResourceKey($resource);

                if (! isset($seen[$key])) {
                    $seen[$key] = true;
                    $collectionIncludes[] = $resource;
                }
            }
        }

        if (! empty($collectionIncludes)) {
            $this->with = array_merge_recursive($this->with, ['included' => $collectionIncludes]);
        }
    }
}
