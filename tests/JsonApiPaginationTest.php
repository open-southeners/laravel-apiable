<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use PHPUnit\Framework\Attributes\Group;

#[Group('requiresDatabase')]
class JsonApiPaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/posts', function () {
            Post::create(['status' => 'Published', 'title' => 'Test Title']);
            Post::create(['status' => 'Published', 'title' => 'Test Title 2']);
            Post::create(['status' => 'Published', 'title' => 'Test Title 3']);
            Post::create(['status' => 'Published', 'title' => 'Test Title 4']);

            return Post::query()->jsonApiPaginate();
        });
    }

    public function test_json_api_pagination_with_page_size()
    {
        $response = $this->getJson('/posts?page[size]=2');

        $response->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasSize(2);
        });

        $response->assertJsonFragment([
            'links' => [
                'first' => url('/posts?page%5Bnumber%5D=1'),
                'last' => url('/posts?page%5Bnumber%5D=2'),
                'prev' => null,
                'next' => url('/posts?page%5Bnumber%5D=2'),
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 2,
                'links' => [
                    [
                        'url' => null,
                        'label' => '&laquo; Previous',
                        'page' => null,
                        'active' => false,
                    ],
                    [
                        'url' => url('/posts?page%5Bnumber%5D=2'),
                        'label' => '2',
                        'page' => 2,
                        'active' => false,
                    ],
                    [
                        'url' => url('/posts?page%5Bnumber%5D=2'),
                        'label' => 'Next &raquo;',
                        'page' => 2,
                        'active' => false,
                    ],
                    [
                        'url' => url('/posts?page%5Bnumber%5D=1'),
                        'label' => '1',
                        'page' => 1,
                        'active' => true,
                    ],
                ],
                'path' => url('/posts'),
                'per_page' => 2,
                'to' => 2,
                'total' => 4,
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_json_api_pagination_with_page_size_and_last_page()
    {
        $response = $this->getJson('/posts?page[size]=2&page[number]=2');

        $response->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasSize(2);
        });

        $response->assertJsonFragment([
            'links' => [
                'first' => url('/posts?page%5Bnumber%5D=1'),
                'last' => url('/posts?page%5Bnumber%5D=2'),
                'prev' => url('/posts?page%5Bnumber%5D=1'),
                'next' => null,
            ],
            'meta' => [
                'current_page' => 2,
                'from' => 3,
                'last_page' => 2,
                'links' => [
                    [
                        'url' => url('/posts?page%5Bnumber%5D=1'),
                        'label' => '&laquo; Previous',
                        'page' => 1,
                        'active' => false,
                    ],
                    [
                        'url' => url('/posts?page%5Bnumber%5D=2'),
                        'label' => '2',
                        'page' => 2,
                        'active' => true,
                    ],
                    [
                        'url' => null,
                        'label' => 'Next &raquo;',
                        'page' => null,
                        'active' => false,
                    ],
                    [
                        'url' => url('/posts?page%5Bnumber%5D=1'),
                        'label' => '1',
                        'page' => 1,
                        'active' => false,
                    ],
                ],
                // TODO: Fix current URL on tests context?
                // "path" => url("/posts?page%5Bnumber%5D=2"),
                'path' => url('/posts'),
                'per_page' => 2,
                'to' => 4,
                'total' => 4,
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_json_api_response_defaults_to_length_aware_pagination()
    {
        $this->createPosts(4);

        Route::get('/posts-response', fn () => JsonApiResponse::from(Post::class));

        $response = $this->getJson('/posts-response?page[size]=2', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonApi(fn (AssertableJsonApi $jsonApi) => $jsonApi->hasSize(2));
        $response->assertJsonPath('meta.total', 4);
        $response->assertJsonPath('meta.last_page', 2);
        $response->assertJsonPath('links.last', url('/posts-response?page%5Bnumber%5D=2'));
    }

    public function test_json_api_response_pagination_type_config_selects_default_strategy()
    {
        $this->createPosts(4);

        config(['apiable.responses.pagination.type' => 'simple']);

        Route::get('/posts-response', fn () => JsonApiResponse::from(Post::class));

        $response = $this->getJson('/posts-response?page[size]=2', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonApi(fn (AssertableJsonApi $jsonApi) => $jsonApi->hasSize(2));
        $response->assertJsonMissingPath('meta.total');
        $response->assertJsonPath('links.last', null);
    }

    public function test_simple_paginating_has_no_total_or_last_page()
    {
        $this->createPosts(4);

        Route::get('/posts-response', fn () => JsonApiResponse::from(Post::class)->simplePaginating(2));

        $response = $this->getJson('/posts-response', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonApi(fn (AssertableJsonApi $jsonApi) => $jsonApi->hasSize(2));
        $response->assertJsonPath('links.last', null);
        $response->assertJsonPath('links.next', url('/posts-response?page%5Bnumber%5D=2'));
        $response->assertJsonMissingPath('meta.total');
        $response->assertJsonMissingPath('meta.last_page');
        $response->assertJsonPath('meta.per_page', 2);
    }

    public function test_cursor_paginating_returns_cursor_bearing_links()
    {
        $this->createPosts(4);

        Route::get('/posts-response', fn () => JsonApiResponse::from(Post::class)->cursorPaginating(2));

        $response = $this->getJson('/posts-response', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonApi(fn (AssertableJsonApi $jsonApi) => $jsonApi->hasSize(2));
        $response->assertJsonPath('links.first', null);
        $response->assertJsonPath('links.last', null);
        $response->assertJsonMissingPath('meta.total');
        $response->assertJsonMissingPath('meta.current_page');
        $response->assertJsonPath('meta.per_page', 2);

        $nextLink = $response->json('links.next');

        $this->assertNotNull($nextLink);
        $this->assertStringContainsString('page%5Bcursor%5D=', $nextLink);

        $nextLinkParts = parse_url($nextLink);

        $followUpResponse = $this->getJson(
            $nextLinkParts['path'].'?'.$nextLinkParts['query'],
            ['Accept' => 'application/vnd.api+json']
        );

        $followUpResponse->assertSuccessful();
        $followUpResponse->assertJsonApi(fn (AssertableJsonApi $jsonApi) => $jsonApi->hasSize(2));
        $followUpResponse->assertJsonPath('links.next', null);

        $prevLink = $followUpResponse->json('links.prev');

        $this->assertNotNull($prevLink);
        $this->assertStringContainsString('page%5Bcursor%5D=', $prevLink);
    }

    public function test_fluent_pagination_method_overrides_config_default()
    {
        $this->createPosts(4);

        config(['apiable.responses.pagination.type' => 'cursor']);

        Route::get('/posts-response', fn () => JsonApiResponse::from(Post::class)->simplePaginating(2));

        $response = $this->getJson('/posts-response', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonApi(fn (AssertableJsonApi $jsonApi) => $jsonApi->hasSize(2));
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonMissingPath('meta.total');
    }

    public function test_page_size_query_param_overrides_simple_paginating_default_size()
    {
        $this->createPosts(4);

        Route::get('/posts-response', fn () => JsonApiResponse::from(Post::class)->simplePaginating());

        $response = $this->getJson('/posts-response?page[size]=1', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonApi(fn (AssertableJsonApi $jsonApi) => $jsonApi->hasSize(1));
        $response->assertJsonPath('meta.per_page', 1);
    }

    public function test_paginate_using_closure_overrides_fluent_pagination_strategy()
    {
        $this->createPosts(4);

        Route::get('/posts-response', fn () => JsonApiResponse::from(Post::class)
            ->cursorPaginating()
            ->paginateUsing(fn ($query) => $query->simplePaginate(2)));

        $response = $this->getJson('/posts-response', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonApi(fn (AssertableJsonApi $jsonApi) => $jsonApi->hasSize(2));
        $response->assertJsonPath('meta.current_page', 1);
    }

    public function test_getting_one_unaffected_by_pagination_strategy()
    {
        $this->createPosts(4);

        Route::get('/posts-response/{post}', fn (Post $post) => JsonApiResponse::from(Post::class)
            ->gettingOne()
            ->cursorPaginating());

        $response = $this->getJson('/posts-response/1', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonApi(fn (AssertableJsonApi $jsonApi) => $jsonApi->isResource());
        $response->assertJsonMissingPath('meta');
        $response->assertJsonMissingPath('links');
    }

    /**
     * Create the given number of posts for pagination tests.
     */
    protected function createPosts(int $amount): void
    {
        for ($i = 1; $i <= $amount; $i++) {
            Post::create(['status' => 'Published', 'title' => "Test Title {$i}"]);
        }
    }
}
