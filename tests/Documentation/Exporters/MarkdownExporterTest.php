<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation\Exporters;

use Illuminate\Support\Facades\File;
use OpenSoutheners\LaravelApiable\Documentation\AuthScheme;
use OpenSoutheners\LaravelApiable\Documentation\Endpoint;
use OpenSoutheners\LaravelApiable\Documentation\Exporters\MarkdownExporter;
use OpenSoutheners\LaravelApiable\Documentation\QueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Resource;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class MarkdownExporterTest extends TestCase
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
                    ],
                    auth: $auth,
                ),
            ],
        );
    }

    public function test_protocol_stub_renders_with_resource_data(): void
    {
        $exporter = new MarkdownExporter(stub: 'protocol', outputPath: '/tmp/apiable-test');
        $outputs = $exporter->export([$this->makeResource()]);

        $this->assertNotEmpty($outputs);
        $content = array_values($outputs)[0];

        $this->assertStringContainsString('Posts', $content);
        $this->assertStringContainsString('List Posts', $content);
    }

    public function test_plain_stub_renders_with_resource_data(): void
    {
        $exporter = new MarkdownExporter(stub: 'plain', outputPath: '/tmp/apiable-test');
        $outputs = $exporter->export([$this->makeResource()]);

        $this->assertNotEmpty($outputs);

        // Plain stub should produce a .md file
        $path = array_key_first($outputs);
        $this->assertStringEndsWith('.md', $path);

        $content = $outputs[$path];
        $this->assertStringContainsString('posts', $content);
    }

    public function test_plain_stub_output_contains_route_uri(): void
    {
        $exporter = new MarkdownExporter(stub: 'plain', outputPath: '/tmp/apiable-test');
        $outputs = $exporter->export([$this->makeResource()]);
        $content = array_values($outputs)[0];

        $this->assertStringContainsString('posts', $content);
    }

    public function test_protocol_stub_output_contains_query_param_key(): void
    {
        $exporter = new MarkdownExporter(stub: 'protocol', outputPath: '/tmp/apiable-test');
        $outputs = $exporter->export([$this->makeResource()]);
        $content = array_values($outputs)[0];

        $this->assertStringContainsString('filter[title][like]', $content);
    }

    public function test_auth_note_rendered_when_auth_present(): void
    {
        $exporter = new MarkdownExporter(stub: 'plain', outputPath: '/tmp/apiable-test');
        $outputs = $exporter->export([$this->makeResource(withAuth: true)]);
        $content = array_values($outputs)[0];

        $this->assertStringContainsString('Authentication required', $content);
    }

    public function test_user_published_stub_takes_priority_over_package_stub(): void
    {
        $userStubPath = base_path('stubs/apiable/docs/plain.md');
        File::ensureDirectoryExists(dirname($userStubPath));
        File::put($userStubPath, 'Custom stub: {{ $resource["name"] }}');

        $exporter = new MarkdownExporter(stub: 'plain', outputPath: '/tmp/apiable-test');
        $outputs = $exporter->export([$this->makeResource()]);
        $content = array_values($outputs)[0];

        File::delete($userStubPath);

        $this->assertStringContainsString('Custom stub: Posts', $content);
    }
}
