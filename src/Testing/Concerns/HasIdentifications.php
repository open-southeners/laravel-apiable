<?php

namespace OpenSoutheners\LaravelApiable\Testing\Concerns;

use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin AssertableJsonApi
 */
trait HasIdentifications
{
    /**
     * Assert that the resource has the specified ID (coerced to string).
     *
     * @param  mixed  $value
     * @return $this
     */
    public function hasId($value): static
    {
        $value = (string) $value;

        PHPUnit::assertTrue(
            $this->resourceId() === $value,
            sprintf('JSON:API response does not have id "%s"', $value)
        );

        return $this;
    }

    /**
     * Assert that the resource has the specified type.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function hasType($value): static
    {
        PHPUnit::assertSame(
            $this->resourceType(),
            $value,
            sprintf('JSON:API response does not have type "%s"', $value)
        );

        return $this;
    }
}
