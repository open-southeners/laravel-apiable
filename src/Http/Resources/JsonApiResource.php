<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

/**
 * @template TResource
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
     * @param  TResource  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;

        $this->attachModelRelations();
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (! $this->evaluateResponse()) {
            return $this->resource;
        }

        $responseArray = $this->getResourceIdentifier();

        return array_merge($responseArray, [
            'attributes' => $this->getAttributes(),
            'relationships' => $this->when(! empty($this->relationships), $this->relationships),
        ]);
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
                $result = $key !== $this->resource->getKeyName() && $value !== null;

                if (! $result) {
                    return false;
                }

                if (! (Apiable::config('responses.include_ids_on_attributes') ?? false)) {
                    return last(explode('_id', $key)) !== '';
                }

                return true;
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
