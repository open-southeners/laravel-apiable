<?php

namespace OpenSoutheners\LaravelApiable\Testing\Concerns;

use Closure;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin AssertableJsonApi
 */
trait HasCollections
{
    /**
     * Assert that the response is a collection.
     *
     * @return $this
     */
    public function isCollection(): static
    {
        PHPUnit::assertNotEmpty($this->collection(), 'Failed asserting that response is a collection');

        return $this;
    }

    /**
     * Scope into the collection item at the given zero-based position.
     *
     * When $callback is provided the callback runs inside the item scope and
     * $this is returned for continued chaining on the collection. When omitted
     * a new scoped instance is returned (backward-compatible usage).
     */
    public function at(int $position, ?Closure $callback = null): static
    {
        $collection = $this->collection();

        if (! array_key_exists($position, $collection)) {
            PHPUnit::fail(sprintf('There is no item at position "%d" on the collection response.', $position));
        }

        if ($callback !== null) {
            return $this->scope('data.'.$position, $callback);
        }

        $scope = new static($collection[$position], 'data.'.$position);
        $scope->rootProps = $this->rootProps ?? $this->prop();

        return $scope;
    }

    /**
     * Assert the number of resources in the collection.
     *
     * @return $this
     */
    public function hasSize(int $value): static
    {
        PHPUnit::assertCount(
            $value,
            $this->collection(),
            sprintf('The collection size is not same as "%d"', $value)
        );

        return $this;
    }
}
