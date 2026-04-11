<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation\Exporters;

use OpenSoutheners\LaravelApiable\Documentation\AuthScheme;
use OpenSoutheners\LaravelApiable\Documentation\Endpoint;
use OpenSoutheners\LaravelApiable\Documentation\Exporters\OpenApiExporter;
use OpenSoutheners\LaravelApiable\Documentation\QueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Resource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class OpenApiExporterTest extends TestCase
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
                        new QueryParam('sort', 'sort', '', '-created_at'),
                    ],
                    auth: $auth,
                ),
            ],
        );
    }

    public function test_export_produces_valid_yaml(): void
    {
        $exporter = new OpenApiExporter('My API', '1.0.0', '/tmp/openapi.yaml');
        $outputs = $exporter->export([$this->makeResource()]);

        $this->assertArrayHasKey('/tmp/openapi.yaml', $outputs);

        $parsed = Yaml::parse($outputs['/tmp/openapi.yaml']);
        $this->assertIsArray($parsed);
    }

    public function test_openapi_version_is_3_1_0(): void
    {
        $exporter = new OpenApiExporter(outputPath: '/tmp/openapi.yaml');
        $outputs = $exporter->export([$this->makeResource()]);

        $parsed = Yaml::parse($outputs['/tmp/openapi.yaml']);
        $this->assertSame('3.1.0', $parsed['openapi']);
    }

    public function test_paths_contains_documented_route(): void
    {
        $exporter = new OpenApiExporter(outputPath: '/tmp/openapi.yaml');
        $outputs = $exporter->export([$this->makeResource()]);

        $parsed = Yaml::parse($outputs['/tmp/openapi.yaml']);
        $this->assertArrayHasKey('/posts', $parsed['paths']);
        $this->assertArrayHasKey('get', $parsed['paths']['/posts']);
    }

    public function test_parameters_are_in_query(): void
    {
        $exporter = new OpenApiExporter(outputPath: '/tmp/openapi.yaml');
        $outputs = $exporter->export([$this->makeResource()]);

        $parsed = Yaml::parse($outputs['/tmp/openapi.yaml']);
        $params = $parsed['paths']['/posts']['get']['parameters'];

        $this->assertNotEmpty($params);
        foreach ($params as $param) {
            $this->assertSame('query', $param['in']);
        }

        $names = array_column($params, 'name');
        $this->assertContains('filter[title][like]', $names);
    }

    public function test_bearer_auth_adds_security_scheme(): void
    {
        $exporter = new OpenApiExporter(outputPath: '/tmp/openapi.yaml');
        $outputs = $exporter->export([$this->makeResource(withAuth: true)]);

        $parsed = Yaml::parse($outputs['/tmp/openapi.yaml']);
        $this->assertArrayHasKey('components', $parsed);
        $this->assertArrayHasKey('bearerAuth', $parsed['components']['securitySchemes']);
        $this->assertSame('bearer', $parsed['components']['securitySchemes']['bearerAuth']['scheme']);
    }

    public function test_bearer_auth_adds_security_requirement_to_operation(): void
    {
        $exporter = new OpenApiExporter(outputPath: '/tmp/openapi.yaml');
        $outputs = $exporter->export([$this->makeResource(withAuth: true)]);

        $parsed = Yaml::parse($outputs['/tmp/openapi.yaml']);
        $operation = $parsed['paths']['/posts']['get'];

        $this->assertArrayHasKey('security', $operation);
    }

    public function test_no_auth_produces_no_components(): void
    {
        $exporter = new OpenApiExporter(outputPath: '/tmp/openapi.yaml');
        $outputs = $exporter->export([$this->makeResource(withAuth: false)]);

        $parsed = Yaml::parse($outputs['/tmp/openapi.yaml']);
        $this->assertArrayNotHasKey('components', $parsed);
    }
}
