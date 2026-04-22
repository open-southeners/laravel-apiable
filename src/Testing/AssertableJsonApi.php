<?php

namespace OpenSoutheners\LaravelApiable\Testing;

use Closure;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Testing\Fluent\AssertableJson;
use OpenSoutheners\LaravelApiable\Testing\Concerns\HasAttributes;
use OpenSoutheners\LaravelApiable\Testing\Concerns\HasCollections;
use OpenSoutheners\LaravelApiable\Testing\Concerns\HasIdentifications;
use OpenSoutheners\LaravelApiable\Testing\Concerns\HasRelationships;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @phpstan-consistent-constructor
 */
class AssertableJsonApi extends AssertableJson
{
    use HasAttributes;
    use HasCollections;
    use HasIdentifications;
    use HasRelationships;
    use Macroable;

    /**
     * Root-level document props; populated in child scopes so top-level members
     * (included, meta, links) remain reachable from any scope depth.
     */
    protected ?array $rootProps = null;

    protected function __construct(array $props, ?string $path = null)
    {
        parent::__construct($props, $path);
    }

    /**
     * Override scope() to thread rootProps into child scopes.
     */
    protected function scope(string $key, Closure $callback): static
    {
        $props = $this->prop($key);
        $path = $this->dotPath($key);

        PHPUnit::assertIsArray($props, sprintf('Property [%s] is not scopeable.', $path));

        $scope = new static($props, $path);
        $scope->rootProps = $this->rootProps ?? $this->prop();
        $callback($scope);
        $scope->interacted();

        return $this;
    }

    public static function fromTestResponse($response): static
    {
        $content = $response->decodeResponseJson()->json();

        static::assertIsJsonApiDocument($content);

        return new static($content);
    }

    protected static function assertIsJsonApiDocument(array $data): void
    {
        $hasData = array_key_exists('data', $data);
        $hasErrors = array_key_exists('errors', $data);
        $hasMeta = array_key_exists('meta', $data);

        PHPUnit::assertTrue(
            $hasData || $hasErrors || $hasMeta,
            'Not a valid JSON:API document: must contain at least one of "data", "errors", or "meta".'
        );

        PHPUnit::assertFalse(
            $hasData && $hasErrors,
            'Not a valid JSON:API document: "data" and "errors" must not coexist.'
        );

        if (array_key_exists('included', $data)) {
            PHPUnit::assertTrue(
                $hasData,
                'Not a valid JSON:API document: "included" requires "data".'
            );
        }
    }

    // -------------------------------------------------------------------------
    // Derived accessors — all JSON:API state is read from props via prop()
    // -------------------------------------------------------------------------

    protected function attributes(): array
    {
        $data = $this->prop('data');

        if (is_array($data) && ! static::responseContainsCollection($data)) {
            return $data['attributes'] ?? [];
        }

        return $this->prop('attributes') ?? [];
    }

    protected function relationships(): array
    {
        $data = $this->prop('data');

        if (is_array($data) && ! static::responseContainsCollection($data)) {
            return $data['relationships'] ?? [];
        }

        return $this->prop('relationships') ?? [];
    }

    protected function resourceId(): string
    {
        $data = $this->prop('data');

        if (is_array($data) && array_key_exists('id', $data)) {
            return (string) $data['id'];
        }

        return (string) ($this->prop('id') ?? '');
    }

    protected function resourceType(): string
    {
        $data = $this->prop('data');

        if (is_array($data) && array_key_exists('type', $data)) {
            return (string) $data['type'];
        }

        return (string) ($this->prop('type') ?? '');
    }

    protected function collection(): array
    {
        $data = $this->prop('data');

        if (is_array($data) && static::responseContainsCollection($data)) {
            return $data;
        }

        return [];
    }

    protected function includedFromRoot(): array
    {
        $root = $this->rootProps ?? $this->prop();

        return data_get($root, 'included', []);
    }

    // -------------------------------------------------------------------------
    // Scoping methods
    // -------------------------------------------------------------------------

    public function data(Closure $callback): static
    {
        return $this->scope('data', $callback);
    }

    public function meta(Closure $callback): static
    {
        return $this->scope('meta', $callback);
    }

    public function links(Closure $callback): static
    {
        return $this->scope('links', $callback);
    }

    public function errors(Closure $callback): static
    {
        return $this->scope('errors', $callback);
    }

    public function included(Closure $callback): static
    {
        return $this->scope('included', $callback);
    }

    public function relationship(string $name, Closure $callback): static
    {
        $prefix = is_array($this->prop('data')) ? 'data.' : '';

        return $this->scope($prefix.'relationships.'.$name.'.data', $callback);
    }

    // -------------------------------------------------------------------------
    // Existing public API
    // -------------------------------------------------------------------------

    /**
     * Check if data contains a collection of resources.
     */
    public static function responseContainsCollection(array $data = []): bool
    {
        return ! array_key_exists('attributes', $data);
    }

    /**
     * Assert that the response is a single resource (not a collection).
     *
     * @return $this
     */
    public function isResource(): static
    {
        PHPUnit::assertEmpty($this->collection(), 'Failed asserting that response is a resource');

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return parent::toArray();
    }

    protected function getIdentifierMessageFor($id = null, ?string $type = null): string
    {
        $messagePrefix = '{ id: %s, type: "%s" }';

        if (! $id && ! $type) {
            return sprintf($messagePrefix, $this->resourceId(), $this->resourceType());
        }

        return sprintf($messagePrefix, (string) $id, $type);
    }
}
