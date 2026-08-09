<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http\Resources;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\TestCase;
use PHPUnit\Framework\AssertionFailedError;

class JsonApiCollectionTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/', function () {
            return Apiable::toJsonApi(collect([
                new Post([
                    'id' => 5,
                    'status' => 'Published',
                    'title' => 'Test Title',
                    'abstract' => 'Test abstract',
                ]),
                new Post([
                    'id' => 6,
                    'status' => 'Published',
                    'title' => 'Test Title 2',
                ]),
            ]));
        });
    }

    public function test_collections_may_be_converted_to_json_api()
    {
        $response = $this->get('/', ['Accept' => 'application/json']);

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                [
                    'id' => '5',
                    'type' => 'post',
                    'attributes' => [
                        'title' => 'Test Title',
                        'abstract' => 'Test abstract',
                    ],
                ],
                [
                    'id' => '6',
                    'type' => 'post',
                    'attributes' => [
                        'title' => 'Test Title 2',
                    ],
                ],
            ],
        ], true);
    }

    public function test_collections_at_has_attribute()
    {
        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->at(0)->hasAttribute('title', 'Test Title');

            $jsonApi->at(1)->hasAttribute('title', 'Test Title 2');
        });
    }

    public function test_collections_at_zero_has_first_item_attribute()
    {
        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->at(0)->hasAttribute('title', 'Test Title');
        });
    }

    public function test_collections_has_size()
    {
        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->hasSize(2);
        });
    }

    public function test_collections_at_unreachable_position()
    {
        $this->expectException(AssertionFailedError::class);

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->at(10);
        });
    }

    public function test_collections_to_array_returns_array()
    {
        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $responseArray = $jsonApi->toArray();

            $this->assertIsArray($responseArray);
            $this->assertFalse(empty($responseArray), 'toArray() should not be empty');
        });
    }

    public function test_collections_with_preserve_query_will_return_pagination_links_with_similar_params()
    {
        Route::get('/posts', function () {
            $postsCollection = collect([
                new Post([
                    'id' => 5,
                    'status' => 'Published',
                    'title' => 'Test Title',
                    'abstract' => 'Test abstract',
                ]),
                new Post([
                    'id' => 6,
                    'status' => 'Published',
                    'title' => 'Test Title 2',
                ]),
            ]);

            return Apiable::toJsonApi(
                new LengthAwarePaginator($postsCollection, $postsCollection->count(), 1)
            )->preserveQuery();
        });

        $response = $this->get('/posts?filter[title]=test', ['Accept' => 'application/json']);

        $response->assertJsonFragment(['url' => '/?filter%5Btitle%5D=test&page=2']);
    }
}
