<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation;

use OpenSoutheners\LaravelApiable\Documentation\EndpointSchemaGenerator;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers\CommentsController;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers\PlansController;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers\PostsController;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers\UsersController;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class EndpointSchemaGeneratorTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware('auth:sanctum')->group(function () use ($router) {
            $router->get('posts', [PostsController::class, 'index']);
            $router->get('posts/{post}', [PostsController::class, 'show']);
        });

        $router->get('comments', [CommentsController::class, 'index']);
        $router->post('comments', [CommentsController::class, 'store']);

        $router->get('plans', [PlansController::class, 'index']);

        $router->get('users', [UsersController::class, 'index']);
    }

    public function test_generates_schema_keyed_by_resource_type_slug(): void
    {
        $generator = new EndpointSchemaGenerator($this->app['router']);
        $schemas = $generator->generate();

        // Sorted by resource slug: label (Tag/comments), plan, post, client (User).
        $this->assertSame(['client', 'label', 'plan', 'post'], array_keys($schemas));
    }

    public function test_posts_schema_matches_controller_filter_sort_and_include_attributes(): void
    {
        $generator = new EndpointSchemaGenerator($this->app['router']);
        $schemas = $generator->generate();

        $post = $schemas['post'];

        $this->assertSame('post', $post['resource']);
        $this->assertSame('/posts', $post['path']);
        $this->assertSame(['operators' => ['like']], $post['filters']['title']);
        $this->assertSame(['created_at'], $post['sorts']);
        $this->assertSame(['author', 'tags'], $post['includes']);
        $this->assertSame([], $post['fields']);
        $this->assertSame([], $post['appends']);
        $this->assertArrayNotHasKey('defaultSort', $post);
        $this->assertArrayNotHasKey('defaultFilters', $post);
    }

    public function test_comments_schema_uses_resource_type_slug_for_fields_and_appends_and_merges_repeated_attributes(): void
    {
        $generator = new EndpointSchemaGenerator($this->app['router']);
        $schemas = $generator->generate();

        // CommentsController's #[EndpointResource(Tag::class)] resolves to 'label' (see tests/TestCase.php).
        $comments = $schemas['label'];

        $this->assertSame('label', $comments['resource']);
        $this->assertSame('/comments', $comments['path']);
        $this->assertSame(['created_at', 'likes'], $comments['sorts']);
        $this->assertSame(['author', 'post'], $comments['includes']);
        $this->assertSame(['label' => ['name']], $comments['fields']);
        $this->assertSame(['label' => ['excerpt']], $comments['appends']);
    }

    public function test_multi_operator_filters_merge_into_one_entry(): void
    {
        $generator = new EndpointSchemaGenerator($this->app['router']);
        $schemas = $generator->generate();

        $plan = $schemas['plan'];

        $this->assertSame(['operators' => ['gte', 'lte']], $plan['filters']['created_at']);
    }

    public function test_restricted_filter_values_are_exposed(): void
    {
        $generator = new EndpointSchemaGenerator($this->app['router']);
        $schemas = $generator->generate();

        $plan = $schemas['plan'];

        $this->assertSame(
            ['operators' => ['equal'], 'values' => ['active', 'archived']],
            $plan['filters']['status']
        );
    }

    public function test_apply_default_sort_and_filter_attributes_populate_defaults(): void
    {
        $generator = new EndpointSchemaGenerator($this->app['router']);
        $schemas = $generator->generate();

        $plan = $schemas['plan'];

        $this->assertSame('-created_at', $plan['defaultSort']);
        $this->assertSame(['status' => 'active'], $plan['defaultFilters']);
    }

    public function test_endpoint_with_no_attributes_still_emits_entry_with_empty_allowances(): void
    {
        $generator = new EndpointSchemaGenerator($this->app['router']);
        $schemas = $generator->generate();

        $users = $schemas['client'];

        $this->assertSame('client', $users['resource']);
        $this->assertSame('/users', $users['path']);
        $this->assertSame([], $users['filters']);
        $this->assertSame([], $users['sorts']);
        $this->assertSame([], $users['includes']);
        $this->assertSame([], $users['fields']);
        $this->assertSame([], $users['appends']);
        $this->assertArrayNotHasKey('defaultSort', $users);
        $this->assertArrayNotHasKey('defaultFilters', $users);
    }

    public function test_only_option_restricts_output_to_matching_routes(): void
    {
        $generator = new EndpointSchemaGenerator($this->app['router']);
        $schemas = $generator->generate(only: ['posts*']);

        $this->assertSame(['post'], array_keys($schemas));
    }

    public function test_exclude_option_drops_matching_routes(): void
    {
        $generator = new EndpointSchemaGenerator($this->app['router']);
        $schemas = $generator->generate(exclude: ['posts*']);

        $this->assertArrayNotHasKey('post', $schemas);
        $this->assertArrayHasKey('label', $schemas);
    }
}
