<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Exceptions\NotJsonApiableModelException;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

/**
 * @property mixed $resource
 * @property array $with
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
     * Eagerload with the resource model the following relationships.
     *
     * @return array
     */
    protected function withRelationships()
    {
        return [];
    }

    /**
     * Attach relationships to the resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected function attachRelations(Model $model)
    {
        $relations = array_filter($model->getRelations(), static function ($value, $key) {
            return $key !== 'pivot' ?: (bool) $value === false;
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($relations as $relation => $relationObj) {
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
        if (! $model instanceof JsonApiable) {
            NotJsonApiableModelException::forModel($model);
        }

        $modelTransformer = $model->jsonApiableOptions()->transformer;
        /** @var \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource $modelResource */
        $modelResource = new $modelTransformer($model);
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
