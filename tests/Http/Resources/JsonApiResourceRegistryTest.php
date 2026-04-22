<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http\Resources;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class PostWithExtraJsonApiResource extends JsonApiResource
{
    protected function withAttributes(): array
    {
        return [
            'computed' => 'computed_value',
        ];
    }
}

class UserWithExtraJsonApiResource extends JsonApiResource
{
    protected function withAttributes(): array
    {
        return [
            'display_name' => strtoupper($this->resource->name),
        ];
    }
}

class JsonApiResourceRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        Apiable::modelResourceMap([]);

        parent::tearDown();
    }

    public function testModelResourceMapRegistersResourceClasses()
    {
        Apiable::modelResourceMap([
            Post::class => PostWithExtraJsonApiResource::class,
        ]);

        $this->assertSame(
            [Post::class => PostWithExtraJsonApiResource::class],
            Apiable::getModelResourceMap()
        );
    }

    public function testJsonApiResourceForReturnsRegisteredClass()
    {
        Apiable::modelResourceMap([
            Post::class => PostWithExtraJsonApiResource::class,
        ]);

        $post = new Post(['id' => 1, 'title' => 'Hello']);

        $this->assertSame(PostWithExtraJsonApiResource::class, Apiable::jsonApiResourceFor($post));
    }

    public function testJsonApiResourceForFallsBackToBaseClass()
    {
        Apiable::modelResourceMap([]);

        $post = new Post(['id' => 1, 'title' => 'Hello']);

        $this->assertSame(JsonApiResource::class, Apiable::jsonApiResourceFor($post));
    }

    public function testToJsonApiUsesRegisteredResourceClass()
    {
        Apiable::modelResourceMap([
            Post::class => PostWithExtraJsonApiResource::class,
        ]);

        $post = new Post(['id' => 1, 'status' => 'Published', 'title' => 'Hello']);

        $resource = Apiable::toJsonApi($post);

        $this->assertInstanceOf(PostWithExtraJsonApiResource::class, $resource);
    }

    public function testToJsonApiWithExplicitResourceClassOverridesRegistry()
    {
        Apiable::modelResourceMap([]);

        $post = new Post(['id' => 1, 'status' => 'Published', 'title' => 'Hello']);

        $resource = Apiable::toJsonApi($post, PostWithExtraJsonApiResource::class);

        $this->assertInstanceOf(PostWithExtraJsonApiResource::class, $resource);
    }

    public function testRelatedResourceUsesRegisteredClassForRelatedModel()
    {
        Apiable::modelResourceMap([
            User::class => UserWithExtraJsonApiResource::class,
        ]);

        Route::get('/', function () {
            $post = new Post(['id' => 5, 'status' => 'Published', 'title' => 'Test Title']);

            $post->setRelation('author', new User([
                'id' => 1,
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'password' => 'secret',
            ]));

            return Apiable::toJsonApi($post);
        });

        $response = $this->get('/', ['Accept' => 'application/json']);

        $response->assertSuccessful();

        $included = $response->json('included');

        $userIncluded = array_values(array_filter($included, fn ($item) => $item['type'] === 'client'));
        $this->assertCount(1, $userIncluded);
        $this->assertSame('ALICE', $userIncluded[0]['attributes']['display_name']);
    }

    public function testParentAndRelatedResourcesUseTheirOwnRegisteredClasses()
    {
        Apiable::modelResourceMap([
            Post::class => PostWithExtraJsonApiResource::class,
            User::class => UserWithExtraJsonApiResource::class,
        ]);

        Route::get('/', function () {
            $post = new Post(['id' => 5, 'status' => 'Published', 'title' => 'My Post']);

            $post->setRelation('author', new User([
                'id' => 1,
                'name' => 'Bob',
                'email' => 'bob@example.com',
                'password' => 'secret',
            ]));

            return Apiable::toJsonApi($post);
        });

        $response = $this->get('/', ['Accept' => 'application/json']);

        $response->assertSuccessful();

        $response->assertJson([
            'data' => [
                'attributes' => [
                    'computed' => 'computed_value',
                ],
            ],
        ]);

        $included = $response->json('included');
        $userIncluded = array_values(array_filter($included, fn ($item) => $item['type'] === 'client'));
        $this->assertCount(1, $userIncluded);
        $this->assertSame('BOB', $userIncluded[0]['attributes']['display_name']);
    }

    public function testToApplicationJsonArrayMergesModelAttributesWithComputedAttributes()
    {
        $post = new Post(['id' => 1, 'status' => 'Published', 'title' => 'Hello', 'abstract' => 'World']);

        $resource = new PostWithExtraJsonApiResource($post);

        $array = $resource->toApplicationJsonArray();

        $this->assertSame('Hello', $array['title']);
        $this->assertSame('computed_value', $array['computed']);
    }

    public function testToApplicationJsonArrayWithBaseResourceReturnsModelAttributes()
    {
        $post = new Post(['id' => 1, 'status' => 'Published', 'title' => 'Hello']);

        $resource = new JsonApiResource($post);

        $array = $resource->toApplicationJsonArray();

        $this->assertSame('Hello', $array['title']);
    }

    public function testJsonApiResponseUsingResourceSetsExplicitClass()
    {
        Route::get('/', function () {
            return Apiable::response(Post::query())
                ->usingResource(PostWithExtraJsonApiResource::class);
        });

        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
    }
}
