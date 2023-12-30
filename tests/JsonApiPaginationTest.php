<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;

/**
 * @group requiresDatabase
 */
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

            return Post::query()->jsonApiPaginate(2);
        });
    }

    public function testJsonApiPaginationWithPageSize()
    {
        $response = $this->getJson('/posts?page[size]=2');

        $response->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasSize(2);
        });

        $response->assertStatus(200);
    }
}
