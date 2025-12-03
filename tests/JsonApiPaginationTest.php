<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

    public function testJsonApiPaginationWithPageSize()
    {
        $response = $this->getJson('/posts?page[size]=2');

        $response->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasSize(2);
        });
        
        $response->assertJsonFragment([
            "links" => [
                "first" => url("/posts?page%5Bnumber%5D=1"),
                "last" => url("/posts?page%5Bnumber%5D=2"),
                "prev" => null,
                "next" => url("/posts?page%5Bnumber%5D=2")
            ],
            "meta" => [
                "current_page" => 1,
                "from" => 1,
                "last_page" => 2,
                "links" => [
                    [
                        "url" => null,
                        "label" => "&laquo; Previous",
                        "page" => null,
                        "active" => false
                    ],
                    [
                        "url" => url("/posts?page%5Bnumber%5D=2"),
                        "label" => "2",
                        "page" => 2,
                        "active" => false
                    ],
                    [
                        "url" => url("/posts?page%5Bnumber%5D=2"),
                        "label" => "Next &raquo;",
                        "page" => 2,
                        "active" => false
                    ],
                    [
                        "url" => url("/posts?page%5Bnumber%5D=1"),
                        "label" => "1",
                        "page" => 1,
                        "active" => true
                    ],
                ],
                "path" => url("/posts"),
                "per_page" => 2,
                "to" => 2,
                "total" => 4,
            ],
        ]);

        $response->assertStatus(200);
    }
    
    public function testJsonApiPaginationWithPageSizeAndLastPage()
    {
        $response = $this->getJson('/posts?page[size]=2&page[number]=2');

        $response->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasSize(2);
        });
        
        $response->assertJsonFragment([
            "links" => [
                "first" => url("/posts?page%5Bnumber%5D=1"),
                "last" => url("/posts?page%5Bnumber%5D=2"),
                "prev" => url("/posts?page%5Bnumber%5D=1"),
                "next" => null
            ],
            "meta" => [
                "current_page" => 2,
                "from" => 3,
                "last_page" => 2,
                "links" => [
                    [
                        "url" => url("/posts?page%5Bnumber%5D=1"),
                        "label" => "&laquo; Previous",
                        "page" => 1,
                        "active" => false
                    ],
                    [
                        "url" => url("/posts?page%5Bnumber%5D=2"),
                        "label" => "2",
                        "page" => 2,
                        "active" => true
                    ],
                    [
                        "url" => null,
                        "label" => "Next &raquo;",
                        "page" => null,
                        "active" => false
                    ],
                    [
                        "url" => url("/posts?page%5Bnumber%5D=1"),
                        "label" => "1",
                        "page" => 1,
                        "active" => false
                    ],
                ],
                // TODO: Fix current URL on tests context?
                // "path" => url("/posts?page%5Bnumber%5D=2"),
                "path" => url("/posts"),
                "per_page" => 2,
                "to" => 4,
                "total" => 4,
            ],
        ]);

        $response->assertStatus(200);
    }
}
