<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
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
     * Hash-set index for O(1) included resource deduplication.
     *
     * @var array<string, true>
     */
    protected array $includedIndex = [];

    /**
     * Current depth of nested resource construction.
     */
    protected int $currentDepth = 0;

    /**
     * Attach relationships to the resource.
     */
    protected function attachModelRelations(): void
    {
        $maxDepth = Apiable::config('responses.max_include_depth');

        if ($maxDepth !== null && $this->currentDepth >= $maxDepth) {
            return;
        }

        $relations = $this->resource->getRelations();

        foreach ($relations as $relation => $relationObj) {
            if (! $relationObj || $relationObj instanceof Pivot) {
                continue;
            }

            if (Apiable::config('responses.normalize_relations') ?? false) {
                $relation = Str::snake($relation);
            }

            if ($relationObj instanceof DatabaseCollection) {
                $this->relationships[$relation]['data'] = [];

                /** @var \Illuminate\Database\Eloquent\Model $relationModel */
                foreach ($relationObj->all() as $relationModel) {
                    $this->processModelRelation($relation, $relationModel);
                }
            }

            if ($relationObj instanceof Model) {
                $this->relationships[$relation]['data'] = null;

                $this->processModelRelation($relation, $relationObj);
            }
        }
    }

    /**
     * Process a model relation attaching to its model additional attributes.
     *
     * @param  \OpenSoutheners\LaravelApiable\Contracts\JsonApiable|\Illuminate\Database\Eloquent\Model  $model
     */
    protected function processModelRelation(string $relation, $model): void
    {
        /** @var \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource $modelResource */
        $modelResource = new self($model);
        $modelResource->currentDepth = $this->currentDepth + 1;
        $modelIdentifier = $modelResource->getResourceIdentifier();

        if (empty($modelIdentifier[$model->getKeyName()] ?? null)) {
            return;
        }

        $resourceRelationshipData = [];

        $resourceRelationshipData = $modelIdentifier;

        $pivotRelations = array_filter($model->getRelations(), fn ($relation) => $relation instanceof Pivot);

        foreach ($pivotRelations as $pivotRelation => $pivotRelationObj) {
            $resourceRelationshipDataMeta = static::filterAttributes($pivotRelationObj, $pivotRelationObj->getAttributes());

            array_walk($resourceRelationshipDataMeta, fn ($value, $key) => ["{$pivotRelation}_{$key}" => $value]);

            $resourceRelationshipData['meta'] = $resourceRelationshipDataMeta;
        }

        if (is_array($this->relationships[$relation]['data'])) {
            $this->relationships[$relation]['data'][] = array_filter($resourceRelationshipData);
        } else {
            $this->relationships[$relation]['data'] = array_filter($resourceRelationshipData);
        }

        $this->addIncluded($modelResource);
    }

    /**
     * Set included data to resource's with.
     */
    protected function addIncluded(JsonApiResource $resource): void
    {
        $this->addToIncludedIfUnique($resource);

        foreach ($resource->getIncluded() as $nestedResource) {
            $this->addToIncludedIfUnique($nestedResource);
        }
    }

    /**
     * Add a resource to the included array if not already present.
     */
    private function addToIncludedIfUnique(JsonApiResource $resource): void
    {
        $key = $this->getResourceKey($resource);

        if (isset($this->includedIndex[$key])) {
            return;
        }

        $this->includedIndex[$key] = true;
        $this->with['included'][] = $resource;
    }

    /**
     * Get resource included relationships.
     */
    public function getIncluded(): array
    {
        return $this->with['included'] ?? [];
    }

    /**
     * Build a unique key string for a JSON:API resource.
     */
    protected function getResourceKey(JsonApiResource $resource): string
    {
        $identifier = $resource->getResourceIdentifier();

        return $identifier['type'] . ':' . $identifier[$resource->resource->getKeyName()];
    }
}
