<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Arr;
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
     */
    protected array $relationships = [];

    /**
     * The resource relationships' pivot attributes.
     */
    protected array $pivotAttributes = [];

    /**
     * Attach relationships to the resource.
     */
    protected function attachModelRelations(): void
    {
        $relations = $this->resource->getRelations();

        foreach ($relations as $relation => $relationObj) {
            if (! $relationObj || ($relationObj instanceof Pivot && ! Apiable::config('responses.include_pivot_attributes', false))) {
                continue;
            }

            if (Apiable::config('responses.normalize_relations') ?? false) {
                $relation = Str::snake($relation);
            }

            if ($relationObj instanceof Pivot) {
                $this->pivotAttributes = array_merge(
                    $this->pivotAttributes,
                    Arr::mapWithKeys(
                        $relationObj->getAttributes(),
                        fn ($value, $key) => ["${relation}_${key}" => $value]
                    )
                );

                continue;
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
     */
    protected function processModelRelation($model): array
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
     */
    protected function addIncluded(JsonApiResource $resource): void
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
     */
    public function getIncluded(): array
    {
        return $this->with['included'] ?? [];
    }

    /**
     * Check and return unique resources on a collection.
     */
    protected function checkUniqueness(Collection $collection): Collection
    {
        return $collection->unique(static function ($resource): string {
            return implode('', $resource->getResourceIdentifier());
        });
    }
}
