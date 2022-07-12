<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

/**
 * @template TResource of \OpenSoutheners\LaravelApiable\Contracts\JsonApiable
 */
class JsonApiResource extends JsonResource
{
    use RelationshipsWithIncludes;

    /**
     * The resource instance.
     *
     * @var TResource
     */
    public $resource;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (empty($this->relationships)) {
            $this->attachRelations($this->resource);
        }

        if ($this->evaluateResponse()) {
            return [
                $this->merge($this->getResourceIdentifier()),
                'attributes' => $this->getAttributes(),
                'relationships' => $this->when(! empty($this->relationships), $this->relationships),
            ];
        }

        return $this->resource;
    }

    /**
     * Test response if valid for formatting.
     *
     * @return bool
     */
    protected function evaluateResponse()
    {
        return ! is_array($this->resource)
            && $this->resource !== null
            && ! $this->resource instanceof MissingValue;
    }

    /**
     * Get object identifier "id" and "type".
     *
     * @return array
     */
    public function getResourceIdentifier()
    {
        return [
            $this->resource->getKeyName() => (string) $this->resource->getKey(),
            'type' => Apiable::getResourceType($this->resource),
        ];
    }

    /**
     * Get filtered attributes excluding all the ids.
     *
     * @return array
     */
    protected function getAttributes()
    {
        return array_filter(
            array_merge($this->resource->attributesToArray(), $this->withAttributes()),
            function ($value, $key) {
                return last(explode('_id', $key)) !== '' && $key !== $this->resource->getKeyName() && $value !== null;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Attach additional attributes data.
     *
     * @return array
     */
    protected function withAttributes()
    {
        return [];
    }
}
