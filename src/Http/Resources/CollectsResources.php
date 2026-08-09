<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Http\Resources\MissingValue;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use ReflectionClass;
use Traversable;

/**
 * @mixin JsonApiCollection
 */
trait CollectsResources
{
    /**
     * Map the given collection resource into its individual resources.
     *
     * @param  MissingValue|AbstractPaginator|Collection<JsonApiable>  $resource
     * @return mixed
     */
    protected function collectResource($resource)
    {
        $collects = $this->collects();

        $this->collection = $collects && ! $resource->first() instanceof $collects
            ? $this->getFiltered($resource, $collects)
            : $resource->toBase();

        return $resource instanceof AbstractPaginator
            ? $resource->setCollection($this->collection)
            : $this->collection;
    }

    /**
     * Get resource collection filtered by authorisation.
     *
     * @param  AbstractPaginator<int, JsonApiable>|Collection<JsonApiable>  $resource
     * @param  class-string<JsonApiResource>  $collects
     * @return Collection
     */
    protected function getFiltered($resource, $collects)
    {
        if ($resource instanceof AbstractPaginator) {
            $resource = $resource->getCollection();
        }

        return $resource->mapInto($collects);
    }

    /**
     * Get the resource that this resource collects.
     *
     * @return string|null
     */
    protected function collects()
    {
        if ($this->collects) {
            return $this->collects;
        }

        if (Str::endsWith(class_basename($this), 'Collection') &&
            class_exists($class = Str::replaceLast('Collection', '', get_class($this)))) {
            return $class;
        }

        return null;
    }

    /**
     * Get the JSON serialization options that should be applied to the resource response.
     *
     * @return int
     */
    public function jsonOptions()
    {
        $collects = $this->collects();

        if (! $collects) {
            return 0;
        }

        return (new ReflectionClass($collects))
            ->newInstanceWithoutConstructor()
            ->jsonOptions();
    }

    /**
     * Get an iterator for the resource collection.
     */
    public function getIterator(): Traversable
    {
        return $this->collection->getIterator();
    }
}
