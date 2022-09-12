<?php

namespace OpenSoutheners\LaravelApiable\Testing\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi
 */
trait HasAttributes
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * Assert that a resource has an attribute with name and value (optional).
     *
     * @param  int|string  $name
     * @param  array<string>|string  $value
     * @return $this
     */
    public function hasAttribute($name, $value = null)
    {
        PHPUnit::assertArrayHasKey($name, $this->attributes, sprintf('JSON:API response does not have an attribute named "%s"', $name));

        if ($value) {
            PHPUnit::assertContains($value, $this->attributes, sprintf('JSON:API response does not have an attribute named "%s" with value "%s"', $name, json_encode($value)));
        }

        return $this;
    }

    /**
     * Assert that a resource does not has an attribute with name and value (optional).
     *
     * @param  int|string  $name
     * @param  array<string>|string  $value
     * @return $this
     */
    public function hasNotAttribute($name, $value = null)
    {
        PHPUnit::assertArrayNotHasKey($name, $this->attributes, sprintf('JSON:API response does not have an attribute named "%s"', $name));

        if ($value) {
            PHPUnit::assertNotContains($value, $this->attributes, sprintf('JSON:API response does not have an attribute named "%s" with value "%s"', $name, json_encode($value)));
        }

        return $this;
    }

    /**
     * Assert that a resource has an array of attributes with names and values (optional).
     *
     * @param  mixed  $attributes
     * @return $this
     */
    public function hasAttributes($attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->hasAttribute($name, $value);
        }

        return $this;
    }

    /**
     * Assert that a resource does not has an array of attributes with names and values (optional).
     *
     * @param  mixed  $attributes
     * @return $this
     */
    public function hasNotAttributes($attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->hasNotAttribute($name, $value);
        }

        return $this;
    }
}
