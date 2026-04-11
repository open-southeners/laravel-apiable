<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

/**
 * Represents a single documented API endpoint.
 */
class Endpoint
{
    /**
     * @param  QueryParam[]  $queryParams
     */
    public function __construct(
        public readonly string $uri,
        public readonly string $method,
        public readonly string $title,
        public readonly string $description,
        public readonly array $queryParams,
        public readonly ?AuthScheme $auth,
    ) {
        //
    }

    /**
     * @return array{uri: string, method: string, title: string, description: string, queryParams: array<array-key, mixed>, auth: array{type: string, middleware: string}|null}
     */
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'method' => $this->method,
            'title' => $this->title,
            'description' => $this->description,
            'queryParams' => array_map(static fn (QueryParam $p) => $p->toArray(), $this->queryParams),
            'auth' => $this->auth?->toArray(),
        ];
    }
}
