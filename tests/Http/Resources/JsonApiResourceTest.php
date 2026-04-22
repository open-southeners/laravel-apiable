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
    public function testResourcesMayBeConvertedToJsonApi()
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

    public function testResourcesHasIdentifier()
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

    public function testResourcesHasAttribute()
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

    public function testResourcesHasAttributes()
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

    public function testResourcesMayBeConvertedToJsonApiWithToJsonMethod()
    {
        $resource = Apiable::toJsonApi(new Post([
            'id' => 5,
            'title' => 'Test Title',
            'abstract' => 'Test abstract',
        ]));

        $this->assertSame('{"id":"5","type":"post","attributes":{"title":"Test Title","abstract":"Test abstract"}}', $resource->toJson());
    }

    public function testResourcesWithRelationshipsMayBeConvertedToJsonApi()
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

    public function testResourcesHasRelationshipWith()
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

    public function testResourcesAtRelationHasAttribute()
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

    public function testSameResourceThroughMultipleRelationshipPathsPreservesNestedIncludes()
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
