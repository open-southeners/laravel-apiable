<?php

namespace OpenSoutheners\LaravelApiable\Testing\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin AssertableJsonApi
 */
trait HasRelationships
{
    /**
     * Scope into the included resource matching the given model instance.
     * When $callback is provided, runs assertions inside that scope and returns
     * $this. When omitted, returns a new scoped instance.
     */
    public function atRelation(Model $model, ?Closure $callback = null): static
    {
        $includeds = $this->includedFromRoot();
        $type = Apiable::getResourceType($model);
        $key = $model->getKey();

        $index = null;

        foreach ($includeds as $i => $included) {
            if ($included['type'] === $type && $included['id'] == $key) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            PHPUnit::fail(sprintf(
                'No included resource found for %s',
                $this->getIdentifierMessageFor($key, $type)
            ));
        }

        if ($callback !== null) {
            return $this->scope('included.'.$index, $callback);
        }

        $scope = new static($includeds[$index], 'included.'.$index);
        $scope->rootProps = $this->rootProps ?? $this->prop();

        return $scope;
    }

    /**
     * Assert that the resource has any relationship (and optionally an included
     * resource) of the given type.
     *
     * @param  mixed  $name
     * @param  bool  $withIncluded
     * @return $this
     */
    public function hasAnyRelationships($name, $withIncluded = false): static
    {
        $type = Apiable::getResourceType($name);

        PHPUnit::assertTrue(
            count($this->filterResources($this->relationships(), $type)) > 0,
            sprintf('There is not any relationship with type "%s"', $type)
        );

        if ($withIncluded) {
            PHPUnit::assertTrue(
                count($this->filterResources($this->includedFromRoot(), $type)) > 0,
                sprintf('There is not any included resource with type "%s"', $type)
            );
        }

        return $this;
    }

    /**
     * Assert that the resource does not have any relationship (and optionally
     * no included resource) of the given type.
     *
     * @param  mixed  $name
     * @param  bool  $withIncluded
     * @return $this
     */
    public function hasNotAnyRelationships($name, $withIncluded = false): static
    {
        $type = Apiable::getResourceType($name);

        PHPUnit::assertFalse(
            count($this->filterResources($this->relationships(), $type)) > 0,
            sprintf('There is a relationship with type "%s" for resource "%s"', $type, $this->getIdentifierMessageFor())
        );

        if ($withIncluded) {
            PHPUnit::assertFalse(
                count($this->filterResources($this->includedFromRoot(), $type)) > 0,
                sprintf('There is an included resource with type "%s"', $type)
            );
        }

        return $this;
    }

    /**
     * Assert that the resource has a relationship with (and optionally an
     * included entry for) the given model instance.
     *
     * @param  bool  $withIncluded
     * @return $this
     */
    public function hasRelationshipWith(Model $model, $withIncluded = false): static
    {
        $type = Apiable::getResourceType($model);

        PHPUnit::assertTrue(
            count($this->filterResources($this->relationships(), $type, $model->getKey())) > 0,
            sprintf(
                'There is no relationship "%s" for resource "%s"',
                $this->getIdentifierMessageFor($model->getKey(), $type),
                $this->getIdentifierMessageFor()
            )
        );

        if ($withIncluded) {
            PHPUnit::assertTrue(
                count($this->filterResources($this->includedFromRoot(), $type, $model->getKey())) > 0,
                sprintf(
                    'There is no included resource "%s"',
                    $this->getIdentifierMessageFor($model->getKey(), $type)
                )
            );
        }

        return $this;
    }

    /**
     * Assert that the resource does not have a relationship with (and optionally
     * no included entry for) the given model instance.
     *
     * @param  bool  $withIncluded
     * @return $this
     */
    public function hasNotRelationshipWith(Model $model, $withIncluded = false): static
    {
        $type = Apiable::getResourceType($model);

        PHPUnit::assertFalse(
            count($this->filterResources($this->relationships(), $type, $model->getKey())) > 0,
            sprintf(
                'There is a relationship "%s" for resource "%s"',
                $this->getIdentifierMessageFor($model->getKey(), $type),
                $this->getIdentifierMessageFor()
            )
        );

        if ($withIncluded) {
            PHPUnit::assertFalse(
                count($this->filterResources($this->includedFromRoot(), $type, $model->getKey())) > 0,
                sprintf(
                    'There is an included resource "%s"',
                    $this->getIdentifierMessageFor($model->getKey(), $type)
                )
            );
        }

        return $this;
    }

    /**
     * Filter array of resources by type and optional id.
     *
     * @param  mixed  $id
     */
    protected function filterResources(array $resources, string $type, $id = null): array
    {
        return array_filter($resources, function ($resource) use ($type, $id) {
            return $this->filterResourceWithIdentifier($resource, $type, $id);
        });
    }

    /**
     * Recursively match a resource (or nested structure) against type/id.
     *
     * @param  mixed  $id
     */
    protected function filterResourceWithIdentifier(array $resource, string $type, $id = null): bool
    {
        if (! isset($resource['type'])) {
            return count($this->filterResources($resource, $type, $id)) > 0;
        }

        $condition = $resource['type'] === $type;

        if ($id) {
            $condition &= $resource['id'] == $id;
        }

        return (bool) $condition;
    }
}
