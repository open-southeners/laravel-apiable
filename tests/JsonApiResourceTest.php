<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;

class JsonApiResourceTest extends TestCase
{
    public function testResourcesMayBeConvertedToJsonApi()
    {
        Route::get('/', function () {
            return Apiable::toJsonApi(new Post([
                'id' => 5,
                'status' => 'Published',
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]));
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
        ]), true);

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
}
