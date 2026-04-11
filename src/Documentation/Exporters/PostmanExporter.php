<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Exporters;

use OpenSoutheners\LaravelApiable\Documentation\AuthScheme;
use OpenSoutheners\LaravelApiable\Documentation\Endpoint;
use OpenSoutheners\LaravelApiable\Documentation\Resource;

class PostmanExporter implements ExporterInterface
{
    private const SCHEMA = 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json';

    public function __construct(
        private readonly string $collectionName = 'API Documentation',
        private readonly string $outputPath = 'postman_collection.json',
    ) {
        //
    }

    /**
     * @param  \OpenSoutheners\LaravelApiable\Documentation\Resource[]  $resources
     * @return array<string, string>
     */
    public function export(array $resources): array
    {
        $collectionAuth = $this->resolveCollectionAuth($resources);

        $collection = [
            'info' => [
                'name' => $this->collectionName,
                'schema' => self::SCHEMA,
            ],
            'item' => array_map(fn (Resource $resource) => $this->buildResourceItem($resource), $resources),
        ];

        if ($collectionAuth !== null) {
            $collection['auth'] = $collectionAuth;
        }

        return [
            $this->outputPath => json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResourceItem(Resource $resource): array
    {
        return [
            'name' => $resource->name,
            'description' => $resource->description,
            'item' => array_map(fn (Endpoint $endpoint) => $this->buildEndpointItem($endpoint), $resource->endpoints),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEndpointItem(Endpoint $endpoint): array
    {
        $headers = [
            ['key' => 'Accept', 'value' => 'application/vnd.api+json'],
            ['key' => 'Content-Type', 'value' => 'application/vnd.api+json'],
        ];

        if ($endpoint->auth !== null) {
            if ($endpoint->auth->type === 'bearer') {
                $headers[] = ['key' => 'Authorization', 'value' => 'Bearer {{token}}'];
            } elseif ($endpoint->auth->type === 'basic') {
                $headers[] = ['key' => 'Authorization', 'value' => 'Basic {{credentials}}'];
            }
        }

        $urlParts = $this->parseUri($endpoint->uri);

        $item = [
            'name' => $endpoint->title,
            'request' => [
                'method' => $endpoint->method,
                'header' => $headers,
                'url' => [
                    'raw' => '{{baseUrl}}/'.$endpoint->uri,
                    'host' => ['{{baseUrl}}'],
                    'path' => $urlParts['path'],
                    'query' => array_map(static fn (array $param) => [
                        'key' => $param['key'],
                        'value' => $param['values'] !== '*' ? $param['values'] : '',
                        'description' => $param['description'],
                        'disabled' => false,
                    ], $endpoint->queryParams ? array_map(static fn ($p) => $p->toArray(), $endpoint->queryParams) : []),
                ],
                'description' => $endpoint->description,
            ],
        ];

        if (! empty($urlParts['variables'])) {
            $item['request']['url']['variable'] = $urlParts['variables'];
        }

        return $item;
    }

    /**
     * @return array{path: list<string>, variables: list<array{key: string, value: string}>}
     */
    private function parseUri(string $uri): array
    {
        $segments = explode('/', trim($uri, '/'));
        $variables = [];

        $pathSegments = array_map(static function (string $segment) use (&$variables): string {
            if (preg_match('/^\{(\w+)\??}$/', $segment, $matches)) {
                $variables[] = ['key' => $matches[1], 'value' => ''];

                return ':'.$matches[1];
            }

            return $segment;
        }, $segments);

        return [
            'path' => $pathSegments,
            'variables' => $variables,
        ];
    }

    /**
     * Determine a collection-level auth block from the first auth scheme found.
     *
     * @param  \OpenSoutheners\LaravelApiable\Documentation\Resource[]  $resources
     * @return array<string, mixed>|null
     */
    private function resolveCollectionAuth(array $resources): ?array
    {
        foreach ($resources as $resource) {
            foreach ($resource->endpoints as $endpoint) {
                if ($endpoint->auth === null) {
                    continue;
                }

                return $this->buildAuthBlock($endpoint->auth);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAuthBlock(AuthScheme $scheme): array
    {
        if ($scheme->type === 'basic') {
            return [
                'type' => 'basic',
                'basic' => [
                    ['key' => 'username', 'value' => '{{username}}', 'type' => 'string'],
                    ['key' => 'password', 'value' => '{{password}}', 'type' => 'string'],
                ],
            ];
        }

        return [
            'type' => 'bearer',
            'bearer' => [
                ['key' => 'token', 'value' => '{{token}}', 'type' => 'string'],
            ],
        ];
    }
}
