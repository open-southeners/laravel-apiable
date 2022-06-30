<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Str;
use ReflectionClass;
use Traversable;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
 */
trait CollectsResources
{
    /**
     * Map the given collection resource into its individual resources.
     *
     * @param  \Illuminate\Http\Resources\MissingValue|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection  $resource
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
     * @param  mixed  $resource
     * @param  mixed  $collects
     * @return \Illuminate\Support\Collection
     */
    protected function getFiltered($resource, $collects)
    {
        if ($resource instanceof AbstractPaginator) {
            $resource = $resource->getCollection();
        }

        $collection = $resource->map(fn ($item) => new $collects($item));

        return $collection;
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
     *
     * @return \ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return $this->collection->getIterator();
    }
}
