<?php

namespace OpenSoutheners\LaravelApiable\Testing;

use Illuminate\Support\Traits\Macroable;
use Illuminate\Testing\Fluent\AssertableJson;
use OpenSoutheners\LaravelApiable\Testing\Concerns\HasAttributes;
use OpenSoutheners\LaravelApiable\Testing\Concerns\HasCollections;
use OpenSoutheners\LaravelApiable\Testing\Concerns\HasIdentifications;
use OpenSoutheners\LaravelApiable\Testing\Concerns\HasRelationships;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\AssertionFailedError;

class AssertableJsonApi extends AssertableJson
{
    use HasAttributes;
    use HasCollections;
    use HasIdentifications;
    use HasRelationships;
    use Macroable;

    /**
     * @var array
     */
    protected $collection;

    protected function __construct($id = '', $type = '', array $attributes = [], array $relationships = [], array $includeds = [], array $collection = [])
    {
        $this->id = $id;
        $this->type = $type;

        $this->attributes = $attributes;
        $this->relationships = $relationships;
        $this->includeds = $includeds;

        $this->collection = $collection;
    }

    public static function fromTestResponse($response)
    {
        try {
            $content = json_decode($response->getContent(), true);
            PHPUnit::assertArrayHasKey('data', $content);
            $data = $content['data'];
            $collection = [];

            if (static::responseContainsCollection($data)) {
                $collection = $data;
                $data = head($data);
            }

            PHPUnit::assertIsArray($data);
            PHPUnit::assertArrayHasKey('id', $data);
            PHPUnit::assertArrayHasKey('type', $data);
            PHPUnit::assertArrayHasKey('attributes', $data);
            PHPUnit::assertIsArray($data['attributes']);
        } catch (AssertionFailedError $e) {
            PHPUnit::fail('Not a valid JSON:API response or response data is empty.');
        }

        return new self($data['id'], $data['type'], $data['attributes'], $data['relationships'] ?? [], $content['included'] ?? [], $collection);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Check if data contains a collection of resources.
     *
     * @return bool
     */
    public static function responseContainsCollection(array $data = [])
    {
        return ! array_key_exists('attributes', $data);
    }

    /**
     * Assert that actual response is a resource
     *
     * @return $this
     */
    public function isResource()
    {
        PHPUnit::assertEmpty($this->collection, 'Failed asserting that response is a resource');

        return $this;
    }

    /**
     * Get the identifier in a pretty printable message by id and type.
     *
     * @param  mixed  $id
     * @return string
     */
    protected function getIdentifierMessageFor($id = null, ?string $type = null)
    {
        $messagePrefix = '{ id: %s, type: "%s" }';

        if (! $id && ! $type) {
            return sprintf($messagePrefix.' at position %d', (string) $this->id, $this->type, $this->atPosition);
        }

        return sprintf($messagePrefix, (string) $id, $type);
    }
}
