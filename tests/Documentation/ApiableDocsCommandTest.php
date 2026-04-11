<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation;

use Illuminate\Support\Facades\File;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers\PostsController;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class ApiableDocsCommandTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempPath = sys_get_temp_dir().'/apiable-docs-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempPath)) {
            File::deleteDirectory($this->tempPath);
        }

        parent::tearDown();
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('auth:sanctum')->group(function () use ($router) {
            $router->get('posts', [PostsController::class, 'index']);
            $router->get('posts/{post}', [PostsController::class, 'show']);
        });
    }

    public function test_generates_markdown_documentation(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['markdown'],
            '--stub' => 'plain',
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $files = File::files($this->tempPath);
        $this->assertNotEmpty($files);

        $mdFiles = array_filter($files, static fn ($f) => str_ends_with($f->getFilename(), '.md'));
        $this->assertNotEmpty($mdFiles);
    }

    public function test_generates_postman_collection(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['postman'],
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $collectionPath = $this->tempPath.'/postman_collection.json';
        $this->assertFileExists($collectionPath);

        $collection = json_decode(File::get($collectionPath), true);
        $this->assertSame('https://schema.getpostman.com/json/collection/v2.1.0/collection.json', $collection['info']['schema']);
    }

    public function test_generates_openapi_yaml(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['openapi'],
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $yamlPath = $this->tempPath.'/openapi.yaml';
        $this->assertFileExists($yamlPath);

        $content = File::get($yamlPath);
        $this->assertStringContainsString('openapi: 3.1.0', $content);
    }

    public function test_generates_multiple_formats_in_single_run(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['markdown', 'postman'],
            '--stub' => 'plain',
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $this->assertFileExists($this->tempPath.'/postman_collection.json');

        $files = File::files($this->tempPath);
        $mdFiles = array_filter($files, static fn ($f) => str_ends_with($f->getFilename(), '.md'));
        $this->assertNotEmpty($mdFiles);
    }

    public function test_only_filter_restricts_output(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['postman'],
            '--only' => ['posts/{post}'],
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $collection = json_decode(File::get($this->tempPath.'/postman_collection.json'), true);
        $items = $collection['item'][0]['item'] ?? [];

        $uris = array_map(
            static fn ($item) => implode('/', $item['request']['url']['path']),
            $items
        );

        $this->assertNotEmpty(array_filter($uris, static fn ($u) => str_contains($u, ':post')));
    }

    public function test_exclude_filter_drops_routes(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['postman'],
            '--exclude' => ['posts/{post}'],
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $collection = json_decode(File::get($this->tempPath.'/postman_collection.json'), true);
        $items = $collection['item'][0]['item'] ?? [];

        $uris = array_map(
            static fn ($item) => implode('/', $item['request']['url']['path']),
            $items
        );

        foreach ($uris as $uri) {
            $this->assertStringNotContainsString(':post', $uri);
        }
    }

    public function test_stub_option_selects_plain_markdown(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['markdown'],
            '--stub' => 'plain',
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $files = File::files($this->tempPath);
        $mdFiles = array_filter($files, static fn ($f) => str_ends_with($f->getFilename(), '.md'));

        $this->assertNotEmpty($mdFiles);
    }
}
