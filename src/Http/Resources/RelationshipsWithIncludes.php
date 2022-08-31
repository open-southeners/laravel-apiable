<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource
 */
trait RelationshipsWithIncludes
{
    /**
     * Included relations on the resource.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Attach relationships to the resource.
     *
     * @return void
     */
    protected function attachModelRelations()
    {
        $relations = $this->resource->getRelations();

        foreach ($relations as $relation => $relationObj) {
            if ($relation === 'pivot' || ! $relationObj) {
                continue;
            }

            if (Apiable::config('normalize_relations', false)) {
                $relation = Str::snake($relation);
            }

            if ($relationObj instanceof DatabaseCollection) {
                /** @var \Illuminate\Database\Eloquent\Model $relationModel */
                foreach ($relationObj->all() as $relationModel) {
                    $this->relationships[$relation]['data'][] = $this->processModelRelation(
                        $relationModel
                    );
                }
            }

            if ($relationObj instanceof Model) {
                $this->relationships[$relation]['data'] = $this->processModelRelation(
                    $relationObj
                );
            }
        }
    }

    /**
     * Process a model relation attaching to its model additional attributes.
     *
     * @param  \OpenSoutheners\LaravelApiable\Contracts\JsonApiable|\Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    protected function processModelRelation($model)
    {
        /** @var \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource $modelResource */
        $modelResource = new self($model);
        $modelIdentifier = $modelResource->getResourceIdentifier();

        if (! empty($modelIdentifier[$model->getKeyName()] ?? null)) {
            $this->addIncluded($modelResource);

            return $modelIdentifier;
        }

        return [];
    }

    /**
     * Set included data to resource's with.
     *
     * @param $resource
     * @return void
     */
    protected function addIncluded(JsonApiResource $resource)
    {
        $includesCol = Collection::make([
            $resource,
            array_values($this->getIncluded()),
            array_values($resource->getIncluded()),
        ])->flatten();

        $includesArr = $this->checkUniqueness($includesCol)->values()->all();

        if (! empty($includesArr)) {
            $this->with = array_merge_recursive($this->with, ['included' => $includesArr]);
        }
    }

    /**
     * Get resource included relationships.
     *
     * @return array
     */
    public function getIncluded()
    {
        return $this->with['included'] ?? [];
    }

    /**
     * Check and return unique resources on a collection.
     *
     * @param \Illuminate\Support\Collection
     * @return \Illuminate\Support\Collection
     */
    protected function checkUniqueness(Collection $collection)
    {
        return $collection->unique(static function ($resource) {
            return implode('', $resource->getResourceIdentifier());
        });
    }
}
