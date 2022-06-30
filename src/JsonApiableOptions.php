<?php

namespace OpenSoutheners\LaravelApiable;

use Exception;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

/**
 * @property-read string $resourceType
 * @property-read string $transformer
 */
class JsonApiableOptions
{
    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var class-string
     */
    protected $transformer;

    /**
     * Create new instance for this class.
     *
     * @param  string  $resourceType
     * @param  class-string  $transformer
     * @return void
     */
    public function __construct($resourceType, $transformer)
    {
        $this->resourceType = $resourceType;
        $this->transformer = $transformer;
    }

    /**
     * New options instance with defaults options for model.
     *
     * @param  string|class-string|\Illuminate\Database\Eloquent\Model  $model
     * @return \OpenSoutheners\LaravelApiable\JsonApiableOptions
     */
    public static function withDefaults($model)
    {
        $resourceType = Apiable::resourceTypeForModel($model);

        return new self($resourceType, JsonApiResource::class);
    }

    /**
     * Set resource type for this model.
     *
     * @param  string  $resourceType
     * @return $this
     */
    public function resourceType($resourceType)
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    /**
     * Set transformer for this model.
     *
     * @param  class-string|string  $class
     * @return $this
     */
    public function transformer($class)
    {
        if (! class_exists($class)) {
            throw new Exception("Transformer class '%s' doesn't exists.", $class);
        }

        $this->transformer = $class;

        return $this;
    }

    /**
     * Get property from this class.
     *
     * @param  mixed  $property
     * @return mixed
     */
    public function __get($property)
    {
        if (! property_exists($this, $property)) {
            return null;
        }

        return $this->{$property};
    }
}
