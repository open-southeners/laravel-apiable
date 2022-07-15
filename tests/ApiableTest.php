<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Plan;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;

class ApiableTest extends TestCase
{
    public function testToJsonApiReturnsFalseWhenValidInput()
    {
        $this->assertFalse(Apiable::toJsonApi(new \stdClass));
        $this->assertFalse(Apiable::toJsonApi('test'));
    }

    public function testToJsonApiReturnsFormattedJsonWhenValidInput()
    {
        $firstPost = new Post(['id' => 1, 'title' => 'foo', 'content' => 'bar', 'status' => 'Published']);
        $secondPost = new Post(['id' => 2, 'title' => 'hello', 'content' => 'world', 'status' => 'Published']);

        $this->assertTrue(Apiable::toJsonApi(new Plan) instanceof JsonApiResource);
        $this->assertTrue(Apiable::toJsonApi($firstPost) instanceof JsonApiResource);
        $this->assertTrue(Apiable::toJsonApi(collect([$firstPost, $secondPost])) instanceof JsonApiCollection);
        $this->assertTrue(Apiable::toJsonApi(Post::query()) instanceof JsonApiCollection);
        $this->assertTrue(Apiable::toJsonApi(Post::paginate()) instanceof JsonApiCollection);
    }
}
