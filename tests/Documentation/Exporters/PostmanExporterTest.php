<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation\Exporters;

use OpenSoutheners\LaravelApiable\Documentation\AuthScheme;
use OpenSoutheners\LaravelApiable\Documentation\Endpoint;
use OpenSoutheners\LaravelApiable\Documentation\Exporters\PostmanExporter;
use OpenSoutheners\LaravelApiable\Documentation\QueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Resource;
use PHPUnit\Framework\TestCase;

class PostmanExporterTest extends TestCase
{
    private function makeResource(bool $withAuth = false): Resource
    {
        $auth = $withAuth ? new AuthScheme('bearer', 'auth:sanctum') : null;

        return new Resource(
            name: 'Posts',
            description: 'Manage blog posts',
            endpoints: [
                new Endpoint(
                    uri: 'posts',
                    method: 'GET',
                    title: 'List Posts',
                    description: 'Get a paginated list of posts',
                    queryParams: [
                        new QueryParam('filter[title][like]', 'filter', 'Filter by title', '*'),
                        new QueryParam('include', 'include', 'Include relationships', 'tags,author'),
                    ],
                    auth: $auth,
                ),
            ],
        );
    }

    public function test_export_produces_valid_postman_collection(): void
    {
        $exporter = new PostmanExporter('My API', '/tmp/test.json');
        $outputs = $exporter->export([$this->makeResource()]);

        $this->assertArrayHasKey('/tmp/test.json', $outputs);

        $collection = json_decode($outputs['/tmp/test.json'], true);

        $this->assertSame('https://schema.getpostman.com/json/collection/v2.1.0/collection.json', $collection['info']['schema']);
        $this->assertSame('My API', $collection['info']['name']);
    }

    public function test_collection_has_nested_items_per_resource(): void
    {
        $exporter = new PostmanExporter('My API', '/tmp/test.json');
        $outputs = $exporter->export([$this->makeResource()]);
        $collection = json_decode($outputs['/tmp/test.json'], true);

        $this->assertCount(1, $collection['item']);
        $this->assertSame('Posts', $collection['item'][0]['name']);
        $this->assertCount(1, $collection['item'][0]['item']);
    }

    public function test_endpoint_has_accept_header(): void
    {
        $exporter = new PostmanExporter('My API', '/tmp/test.json');
        $outputs = $exporter->export([$this->makeResource()]);
        $collection = json_decode($outputs['/tmp/test.json'], true);

        $headers = $collection['item'][0]['item'][0]['request']['header'];
        $headerKeys = array_column($headers, 'key');

        $this->assertContains('Accept', $headerKeys);

        $acceptValue = $headers[array_search('Accept', $headerKeys)]['value'];
        $this->assertSame('application/vnd.api+json', $acceptValue);
    }

    public function test_endpoint_has_query_parameters(): void
    {
        $exporter = new PostmanExporter('My API', '/tmp/test.json');
        $outputs = $exporter->export([$this->makeResource()]);
        $collection = json_decode($outputs['/tmp/test.json'], true);

        $query = $collection['item'][0]['item'][0]['request']['url']['query'];
        $keys = array_column($query, 'key');

        $this->assertContains('filter[title][like]', $keys);
        $this->assertContains('include', $keys);
    }

    public function test_bearer_auth_adds_authorization_header(): void
    {
        $exporter = new PostmanExporter('My API', '/tmp/test.json');
        $outputs = $exporter->export([$this->makeResource(withAuth: true)]);
        $collection = json_decode($outputs['/tmp/test.json'], true);

        $headers = $collection['item'][0]['item'][0]['request']['header'];
        $authHeader = null;
        foreach ($headers as $h) {
            if ($h['key'] === 'Authorization') {
                $authHeader = $h;
                break;
            }
        }

        $this->assertNotNull($authHeader);
        $this->assertStringContainsString('Bearer', $authHeader['value']);
    }

    public function test_bearer_auth_sets_collection_auth_block(): void
    {
        $exporter = new PostmanExporter('My API', '/tmp/test.json');
        $outputs = $exporter->export([$this->makeResource(withAuth: true)]);
        $collection = json_decode($outputs['/tmp/test.json'], true);

        $this->assertArrayHasKey('auth', $collection);
        $this->assertSame('bearer', $collection['auth']['type']);
    }

    public function test_no_auth_produces_no_collection_auth_block(): void
    {
        $exporter = new PostmanExporter('My API', '/tmp/test.json');
        $outputs = $exporter->export([$this->makeResource(withAuth: false)]);
        $collection = json_decode($outputs['/tmp/test.json'], true);

        $this->assertArrayNotHasKey('auth', $collection);
    }
}
