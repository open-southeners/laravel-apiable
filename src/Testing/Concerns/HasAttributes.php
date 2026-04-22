<?php

namespace OpenSoutheners\LaravelApiable\Testing\Concerns;

use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin AssertableJsonApi
 */
trait HasAttributes
{
    /**
     * Assert that a resource has an attribute with name and optionally an exact value.
     *
     * @param  int|string  $name
     * @param  mixed  $value
     * @return $this
     */
    public function hasAttribute($name, $value = null): static
    {
        $attrs = $this->attributes();

        PHPUnit::assertArrayHasKey(
            $name,
            $attrs,
            sprintf('JSON:API response does not have an attribute named "%s"', $name)
        );

        if ($value !== null) {
            PHPUnit::assertSame(
                $value,
                $attrs[$name],
                sprintf('JSON:API response attribute "%s" does not equal %s', $name, json_encode($value))
            );
        }

        return $this;
    }

    /**
     * Assert that a resource does not have an attribute with name, or has the attribute
     * but with a different value when $value is provided.
     *
     * @param  int|string  $name
     * @param  mixed  $value
     * @return $this
     */
    public function hasNotAttribute($name, $value = null): static
    {
        $attrs = $this->attributes();

        if ($value !== null) {
            if (array_key_exists($name, $attrs)) {
                PHPUnit::assertNotSame(
                    $value,
                    $attrs[$name],
                    sprintf('JSON:API response attribute "%s" unexpectedly equals %s', $name, json_encode($value))
                );
            }
        } else {
            PHPUnit::assertArrayNotHasKey(
                $name,
                $attrs,
                sprintf('JSON:API response unexpectedly has an attribute named "%s"', $name)
            );
        }

        return $this;
    }

    /**
     * Assert that a resource has the given attributes.
     * Pass a list ['name1', 'name2'] to check key existence only, or a map
     * ['name' => value] to also assert the exact value.
     *
     * @param  mixed  $attributes
     * @return $this
     */
    public function hasAttributes($attributes): static
    {
        foreach ($attributes as $name => $value) {
            if (is_int($name)) {
                $this->hasAttribute($value);
            } else {
                $this->hasAttribute($name, $value);
            }
        }

        return $this;
    }

    /**
     * Assert that a resource does not have the given attributes.
     *
     * @param  mixed  $attributes
     * @return $this
     */
    public function hasNotAttributes($attributes): static
    {
        foreach ($attributes as $name => $value) {
            if (is_int($name)) {
                $this->hasNotAttribute($value);
            } else {
                $this->hasNotAttribute($name, $value);
            }
        }

        return $this;
    }
}
