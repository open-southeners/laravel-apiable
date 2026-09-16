<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelApiable\Http\Resources\Json\ResourceCollection;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

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
     * @param  class-string<JsonApiResource>|null  $collects
     * @return void
     */
    public function __construct($resource, $collects = null)
    {
        $this->collects = $collects ?: JsonApiResource::class;

        if ($collects === null) {
            $items = $resource instanceof AbstractPaginator || $resource instanceof AbstractCursorPaginator
                ? $resource->getCollection()
                : $resource;

            if (is_array($items)) {
                $items = new Collection($items);
            }

            if ($items instanceof Collection) {
                $items = $items->map(function ($item) {
                    if (! $item instanceof Model) {
                        return $item;
                    }

                    $resourceClass = Apiable::jsonApiResourceFor($item);

                    return new $resourceClass($item);
                });

                $resource = $resource instanceof AbstractPaginator || $resource instanceof AbstractCursorPaginator
                    ? $resource->setCollection($items)
                    : $items;
            }
        }

        parent::__construct($resource);

        $this->withIncludes();
    }
}
