<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation;

use OpenSoutheners\LaravelApiable\Documentation\Generator;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers\CommentsController;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers\PostsController;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class GeneratorTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware('auth:sanctum')->group(function () use ($router) {
            $router->get('posts', [PostsController::class, 'index']);
            $router->get('posts/{post}', [PostsController::class, 'show']);
        });

        $router->get('comments', [CommentsController::class, 'index']);
        $router->post('comments', [CommentsController::class, 'store']);

        // These should be filtered out by excluded_routes config
        $router->get('_debugbar/assets/javascript', fn () => '');
        $router->get('telescope/requests', fn () => '');
    }

    public function test_generates_resource_tree_from_annotated_controller(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate();

        $this->assertCount(2, $resources);
        $this->assertSame('Posts', $resources[0]->name);
        $this->assertSame('Manage blog posts', $resources[0]->description);
    }

    public function test_excludes_routes_matching_config_patterns(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate();

        $uris = array_merge(...array_map(
            static fn ($r) => array_map(static fn ($e) => $e->uri, $r->endpoints),
            $resources
        ));

        $this->assertNotContains('_debugbar/assets/javascript', $uris);
        $this->assertNotContains('telescope/requests', $uris);
    }

    public function test_generates_endpoints_for_each_route_method(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate();

        $endpoints = $resources[0]->endpoints;

        $this->assertGreaterThanOrEqual(2, count($endpoints));

        $uris = array_map(static fn ($e) => $e->uri, $endpoints);
        $this->assertContains('posts', $uris);
        $this->assertContains('posts/{post}', $uris);
    }

    public function test_endpoint_has_query_params_from_attributes(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate();

        $indexEndpoint = null;
        foreach ($resources[0]->endpoints as $endpoint) {
            if ($endpoint->uri === 'posts' && $endpoint->method === 'GET') {
                $indexEndpoint = $endpoint;
                break;
            }
        }

        $this->assertNotNull($indexEndpoint);
        $this->assertNotEmpty($indexEndpoint->queryParams);

        $keys = array_map(static fn ($p) => $p->key, $indexEndpoint->queryParams);
        $this->assertContains('filter[title][like]', $keys);
        $this->assertContains('sort', $keys);
        $this->assertContains('include', $keys);
    }

    public function test_auth_scheme_detected_from_middleware(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate();

        foreach ($resources[0]->endpoints as $endpoint) {
            $this->assertNotNull($endpoint->auth);
            $this->assertSame('bearer', $endpoint->auth->type);
        }
    }

    public function test_only_filter_restricts_to_matching_routes(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate(only: ['posts/{post}']);

        $uris = array_merge(...array_map(
            static fn ($r) => array_map(static fn ($e) => $e->uri, $r->endpoints),
            $resources
        ));

        $this->assertContains('posts/{post}', $uris);
        $this->assertNotContains('posts', $uris);
    }

    public function test_exclude_filter_drops_matching_routes(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate(exclude: ['posts/{post}']);

        $uris = array_merge(...array_map(
            static fn ($r) => array_map(static fn ($e) => $e->uri, $r->endpoints),
            $resources
        ));

        $this->assertNotContains('posts/{post}', $uris);
    }

    public function test_model_class_resolved_from_endpoint_resource_attribute(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate();

        $this->assertNotNull($resources[0]->modelClass);
        $this->assertStringContainsString('Post', $resources[0]->modelClass);
    }

    public function test_controllers_without_documented_resource_attribute_are_skipped(): void
    {
        // The route we defined with closure has no controller, so it gets skipped.
        // We just verify the total resource count matches the annotated controllers
        // (PostsController and CommentsController).
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate();

        $this->assertCount(2, $resources);
    }

    public function test_repeated_sort_and_include_attributes_merge_into_one_param_each(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate();

        $commentsResource = null;
        foreach ($resources as $resource) {
            if ($resource->name === 'Comments') {
                $commentsResource = $resource;
                break;
            }
        }

        $this->assertNotNull($commentsResource);

        $indexEndpoint = null;
        foreach ($commentsResource->endpoints as $endpoint) {
            if ($endpoint->uri === 'comments' && $endpoint->method === 'GET') {
                $indexEndpoint = $endpoint;
                break;
            }
        }

        $this->assertNotNull($indexEndpoint);

        $sortParams = array_values(array_filter($indexEndpoint->queryParams, static fn ($p) => $p->key === 'sort'));
        $includeParams = array_values(array_filter($indexEndpoint->queryParams, static fn ($p) => $p->key === 'include'));

        $this->assertCount(1, $sortParams);
        $this->assertCount(1, $includeParams);

        $this->assertStringContainsString('created_at', $sortParams[0]->values);
        $this->assertStringContainsString('likes', $sortParams[0]->values);

        $this->assertStringContainsString('author', $includeParams[0]->values);
        $this->assertStringContainsString('post', $includeParams[0]->values);
    }

    public function test_appends_and_fields_params_use_the_runtime_resource_type_slug(): void
    {
        $generator = new Generator($this->app['router']);
        $resources = $generator->generate();

        $commentsResource = null;
        foreach ($resources as $resource) {
            if ($resource->name === 'Comments') {
                $commentsResource = $resource;
                break;
            }
        }

        $this->assertNotNull($commentsResource);

        $indexEndpoint = null;
        foreach ($commentsResource->endpoints as $endpoint) {
            if ($endpoint->uri === 'comments' && $endpoint->method === 'GET') {
                $indexEndpoint = $endpoint;
                break;
            }
        }

        $this->assertNotNull($indexEndpoint);

        $keys = array_map(static fn ($p) => $p->key, $indexEndpoint->queryParams);

        // Tag::class is mapped to 'label' in tests/TestCase.php, not its FQCN or default slug.
        $this->assertContains('appends[label]', $keys);
        $this->assertContains('fields[label]', $keys);
        $this->assertNotContains('appends[OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag]', $keys);
    }
}
