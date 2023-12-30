<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use OpenSoutheners\LaravelApiable\Http\Resources\Json\ResourceCollection;

/**
 * @template TCollectedResource
 *
 * @extends ResourceCollection<TCollectedResource>
 */
class JsonApiCollection extends ResourceCollection
{
    use CollectsWithIncludes;

    /**
     * Create a new resource instance.
     *
     * @param  TCollectedResource  $resource
     * @param  class-string<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource>|null  $collects
     * @return void
     */
    public function __construct($resource, $collects = null)
    {
        $this->collects = $collects ?: JsonApiResource::class;

        parent::__construct($resource);

        $this->withIncludes();
    }
}
