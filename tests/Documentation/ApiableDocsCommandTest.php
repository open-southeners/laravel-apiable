<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation;

use Illuminate\Support\Facades\File;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers\CommentsController;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers\PostsController;
use OpenSoutheners\LaravelApiable\Tests\TestCase;
use Symfony\Component\Yaml\Yaml;

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

        $router->get('comments', [CommentsController::class, 'index']);
        $router->post('comments', [CommentsController::class, 'store']);
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

    public function test_relative_path_writes_files_once_without_doubling(): void
    {
        $originalCwd = getcwd();
        $scratchRoot = sys_get_temp_dir().'/apiable-docs-relative-'.uniqid();
        File::ensureDirectoryExists($scratchRoot);
        chdir($scratchRoot);

        try {
            $this->artisan('apiable:docs', [
                '--format' => ['openapi'],
                '--path' => 'docs/api',
            ])->assertExitCode(0);

            $this->assertFileExists($scratchRoot.'/docs/api/openapi.yaml');
            // The path-doubling bug would have written this instead of the path above.
            $this->assertFileDoesNotExist($scratchRoot.'/docs/api/docs/api/openapi.yaml');

            $this->assertCount(1, File::allFiles($scratchRoot));
        } finally {
            chdir($originalCwd);
            File::deleteDirectory($scratchRoot);
        }
    }

    public function test_openapi_output_has_single_sort_and_include_param_for_repeated_attributes(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['openapi'],
            '--only' => ['comments'],
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $parsed = Yaml::parse(File::get($this->tempPath.'/openapi.yaml'));
        $names = array_column($parsed['paths']['/comments']['get']['parameters'], 'name');

        $this->assertCount(1, array_filter($names, static fn ($n) => $n === 'sort'));
        $this->assertCount(1, array_filter($names, static fn ($n) => $n === 'include'));
    }

    public function test_openapi_output_uses_resource_type_slug_for_appends_and_fields(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['openapi'],
            '--only' => ['comments'],
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $parsed = Yaml::parse(File::get($this->tempPath.'/openapi.yaml'));
        $names = array_column($parsed['paths']['/comments']['get']['parameters'], 'name');

        // Tag::class is mapped to 'label' in tests/TestCase.php, not its FQCN.
        $this->assertContains('appends[label]', $names);
        $this->assertContains('fields[label]', $names);
    }

    public function test_markdown_output_uses_method_flag_for_write_endpoint(): void
    {
        $this->artisan('apiable:docs', [
            '--format' => ['markdown'],
            '--stub' => 'plain',
            '--only' => ['comments'],
            '--path' => $this->tempPath,
        ])->assertExitCode(0);

        $files = File::files($this->tempPath);
        $commentsFile = null;

        foreach ($files as $file) {
            if (str_contains($file->getFilename(), 'comments')) {
                $commentsFile = $file;
                break;
            }
        }

        $this->assertNotNull($commentsFile);

        $content = File::get($commentsFile->getPathname());
        $this->assertStringContainsString('-X POST', $content);
    }
}
