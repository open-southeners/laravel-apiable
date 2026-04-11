<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

/**
 * Groups a set of documented endpoints under a named API resource.
 */
class Resource
{
    /**
     * @param  Endpoint[]  $endpoints
     * @param  class-string<\Illuminate\Database\Eloquent\Model>|null  $modelClass
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $endpoints,
        public readonly ?string $modelClass = null,
    ) {
        //
    }

    /**
     * @return array{name: string, description: string, endpoints: array<array-key, mixed>, modelClass: string|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'endpoints' => array_map(static fn (Endpoint $e) => $e->toArray(), $this->endpoints),
            'modelClass' => $this->modelClass,
        ];
    }
}
