<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http\Resources;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
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

    public function test_model_resource_map_registers_resource_classes()
    {
        Apiable::modelResourceMap([
            Post::class => PostWithExtraJsonApiResource::class,
        ]);

        $this->assertSame(
            [Post::class => PostWithExtraJsonApiResource::class],
            Apiable::getModelResourceMap()
        );
    }

    public function test_json_api_resource_for_returns_registered_class()
    {
        Apiable::modelResourceMap([
            Post::class => PostWithExtraJsonApiResource::class,
        ]);

        $post = new Post(['id' => 1, 'title' => 'Hello']);

        $this->assertSame(PostWithExtraJsonApiResource::class, Apiable::jsonApiResourceFor($post));
    }

    public function test_json_api_resource_for_falls_back_to_base_class()
    {
        Apiable::modelResourceMap([]);

        $post = new Post(['id' => 1, 'title' => 'Hello']);

        $this->assertSame(JsonApiResource::class, Apiable::jsonApiResourceFor($post));
    }

    public function test_to_json_api_uses_registered_resource_class()
    {
        Apiable::modelResourceMap([
            Post::class => PostWithExtraJsonApiResource::class,
        ]);

        $post = new Post(['id' => 1, 'status' => 'Published', 'title' => 'Hello']);

        $resource = Apiable::toJsonApi($post);

        $this->assertInstanceOf(PostWithExtraJsonApiResource::class, $resource);
    }

    public function test_to_json_api_with_explicit_resource_class_overrides_registry()
    {
        Apiable::modelResourceMap([]);

        $post = new Post(['id' => 1, 'status' => 'Published', 'title' => 'Hello']);

        $resource = Apiable::toJsonApi($post, PostWithExtraJsonApiResource::class);

        $this->assertInstanceOf(PostWithExtraJsonApiResource::class, $resource);
    }

    public function test_collections_and_paginators_use_each_models_registered_resource()
    {
        Apiable::modelResourceMap([
            Post::class => PostWithExtraJsonApiResource::class,
            User::class => UserWithExtraJsonApiResource::class,
        ]);

        $post = new Post(['id' => 1, 'status' => 'Active', 'title' => 'Hello']);
        $user = new User(['id' => 2, 'name' => 'Alice']);

        foreach ([collect([$post, $user]), new LengthAwarePaginator(collect([$post, $user]), 2, 10)] as $items) {
            $collection = Apiable::toJsonApi($items);

            $this->assertInstanceOf(PostWithExtraJsonApiResource::class, $collection->collection[0]);
            $this->assertInstanceOf(UserWithExtraJsonApiResource::class, $collection->collection[1]);
        }
    }

    public function test_builder_response_uses_registered_resource_and_explicit_override()
    {
        Apiable::modelResourceMap([Post::class => PostWithExtraJsonApiResource::class]);
        Post::create(['status' => 'Active', 'title' => 'Hello']);

        Route::get('/mapped-posts', fn () => Apiable::response(Post::query()));
        Route::get('/explicit-posts', fn () => Apiable::response(Post::query())
            ->usingResource(JsonApiResource::class));

        $this->get('/mapped-posts', ['Accept' => 'application/vnd.api+json'])
            ->assertJsonPath('data.0.attributes.computed', 'computed_value');
        $this->assertArrayNotHasKey(
            'computed',
            $this->get('/explicit-posts', ['Accept' => 'application/vnd.api+json'])->json('data.0.attributes')
        );
    }

    public function test_related_resource_uses_registered_class_for_related_model()
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

    public function test_parent_and_related_resources_use_their_own_registered_classes()
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

    public function test_to_application_json_array_merges_model_attributes_with_computed_attributes()
    {
        $post = new Post(['id' => 1, 'status' => 'Published', 'title' => 'Hello', 'abstract' => 'World']);

        $resource = new PostWithExtraJsonApiResource($post);

        $array = $resource->toApplicationJsonArray();

        $this->assertSame('Hello', $array['title']);
        $this->assertSame('computed_value', $array['computed']);
    }

    public function test_to_application_json_array_with_base_resource_returns_model_attributes()
    {
        $post = new Post(['id' => 1, 'status' => 'Published', 'title' => 'Hello']);

        $resource = new JsonApiResource($post);

        $array = $resource->toApplicationJsonArray();

        $this->assertSame('Hello', $array['title']);
    }

    public function test_json_api_response_using_resource_sets_explicit_class()
    {
        Route::get('/', function () {
            return Apiable::response(Post::query())
                ->usingResource(PostWithExtraJsonApiResource::class);
        });

        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
    }
}
