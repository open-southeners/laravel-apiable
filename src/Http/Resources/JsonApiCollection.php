<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use OpenSoutheners\LaravelApiable\Http\Resources\Json\ResourceCollection;

class JsonApiCollection extends ResourceCollection
{
    use CollectsWithIncludes;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
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
