<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http\Resources;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class JsonApiResourceTest extends TestCase
{
    public function test_resources_may_be_converted_to_json_api()
    {
        Route::get('/', function () {
            return (new Post([
                'id' => 5,
                'status' => 'Published',
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]))->toJsonApi();
        });

        $response = $this->get('/', ['Accept' => 'application/json']);

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id' => '5',
                'type' => 'post',
                'attributes' => [
                    'title' => 'Test Title',
                    'abstract' => 'Test abstract',
                ],
            ],
        ], true);
    }

    public function test_resources_has_identifier()
    {
        Route::get('/', function () {
            return Apiable::toJsonApi(new Post([
                'id' => 5,
                'status' => 'Published',
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]));
        });

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasId(5)->hasType('post');
        });
    }

    public function test_resources_has_attribute()
    {
        Route::get('/', function () {
            return Apiable::toJsonApi(new Post([
                'id' => 5,
                'status' => 'Published',
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]));
        });

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasAttribute('title', 'Test Title');
        });
    }

    public function test_resources_has_attributes()
    {
        Route::get('/', function () {
            return Apiable::toJsonApi(new Post([
                'id' => 5,
                'status' => 'Published',
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]));
        });

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasAttributes([
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]);
        });
    }

    public function test_resources_may_be_converted_to_json_api_with_to_json_method()
    {
        $resource = Apiable::toJsonApi(new Post([
            'id' => 5,
            'title' => 'Test Title',
            'abstract' => 'Test abstract',
        ]));

        $this->assertSame('{"id":"5","type":"post","attributes":{"title":"Test Title","abstract":"Test abstract"}}', $resource->toJson());
    }

    public function test_resources_with_relationships_may_be_converted_to_json_api()
    {
        Route::get('/', function () {
            $post = new Post([
                'id' => 5,
                'status' => 'Published',
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]);

            $post->setRelation('parent', new Post([
                'id' => 4,
                'title' => 'Test Parent Title',
            ]));

            return Apiable::toJsonApi($post);
        });

        $response = $this->get('/', ['Accept' => 'application/json']);

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id' => '5',
                'type' => 'post',
                'attributes' => [
                    'title' => 'Test Title',
                    'abstract' => 'Test abstract',
                ],
                'relationships' => [
                    'parent' => [
                        'data' => [
                            'id' => '4',
                            'type' => 'post',
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => '4',
                    'type' => 'post',
                    'attributes' => [
                        'title' => 'Test Parent Title',
                    ],
                ],
            ],
        ], true);
    }

    public function test_resources_has_relationship_with()
    {
        Route::get('/', function () {
            $post = new Post([
                'id' => 5,
                'status' => 'Published',
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]);

            $post->setRelation('parent', new Post([
                'id' => 4,
                'status' => 'Published',
                'title' => 'Test Parent Title',
            ]));

            return Apiable::toJsonApi($post);
        });

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasRelationshipWith(new Post([
                'id' => 4,
                'title' => 'Test Parent Title',
            ]), true);
        });
    }

    public function test_resources_at_relation_has_attribute()
    {
        Route::get('/', function () {
            $post = new Post([
                'id' => 5,
                'status' => 'Published',
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]);

            $post->setRelation('parent', new Post([
                'id' => 4,
                'status' => 'Published',
                'title' => 'Test Parent Title',
            ]));

            return Apiable::toJsonApi($post);
        });

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->atRelation(new Post([
                'id' => 4,
                'status' => 'Published',
                'title' => 'Test Parent Title',
            ]))->hasAttribute('title', 'Test Parent Title');
        });
    }

    public function test_same_resource_through_multiple_relationship_paths_preserves_nested_includes()
    {
        Route::get('/', function () {
            // User ID=2 appearing as 'editor' without any nested includes
            $editorUser = new User(['id' => 2, 'name' => 'John', 'email' => 'john@example.com']);

            // Same User ID=2 appearing as 'author' but with a nested post loaded
            $authorUser = new User(['id' => 2, 'name' => 'John', 'email' => 'john@example.com']);
            $authorUser->setRelation('latestPost', new Post([
                'id' => 10,
                'status' => 'Published',
                'title' => 'Authored Post',
            ]));

            $post = new Post(['id' => 5, 'status' => 'Published', 'title' => 'Test Title']);
            $post->setRelation('editor', $editorUser);
            $post->setRelation('author', $authorUser);

            return Apiable::toJsonApi($post);
        });

        $response = $this->get('/', ['Accept' => 'application/json']);

        $response->assertStatus(200);

        $included = $response->json('included');

        // User ID=2 should appear exactly once (deduplicated); User maps to 'client' type
        $users = array_values(array_filter($included, fn ($item) => $item['type'] === 'client'));
        $this->assertCount(1, $users);
        $this->assertSame('2', $users[0]['id']);

        // The nested post from 'author.latestPost' should be preserved (the more complete version wins)
        $nestedPosts = array_values(array_filter($included, fn ($item) => $item['type'] === 'post' && $item['id'] === '10'));
        $this->assertCount(1, $nestedPosts);
    }
}
