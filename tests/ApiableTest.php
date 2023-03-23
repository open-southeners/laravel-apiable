<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use OpenSoutheners\LaravelApiable\Http\AllowedAppends;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Plan;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;

class ApiableTest extends TestCase
{
    public function testToJsonApiReturnsEmptyJsonApiCollectionWhenInvalidInput()
    {
        $this->assertEquals(new JsonApiCollection(Collection::make([])), Apiable::toJsonApi(new \stdClass));
        $this->assertEquals(new JsonApiCollection(Collection::make([])), Apiable::toJsonApi('test'));
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

    public function testResponseReturnsTrueWhenValidInput()
    {
        $this->assertTrue(Apiable::response(Post::query()) instanceof JsonApiResponse);
        $this->assertTrue(
            Apiable::response(Post::query(), [
                AllowedAppends::make('post', ['abstract']),
            ]) instanceof JsonApiResponse
        );

        $this->assertCount(
            1,
            Apiable::response(Post::query())->allowing([
                AllowedAppends::make('post', ['abstract']),
            ])->getAllowedAppends()
        );
    }

    public function testGetModelResourceTypeMapGetsNonEmptyArray()
    {
        $this->assertIsArray(Apiable::getModelResourceTypeMap());
        $this->assertNotEmpty(Apiable::getModelResourceTypeMap());
    }

    public function testModelResourceTypeMapSetsReplacingPreviousArray()
    {
        $this->assertNotEmpty(Apiable::getModelResourceTypeMap());
        Apiable::modelResourceTypeMap([]);
        $this->assertEmpty(Apiable::getModelResourceTypeMap());
    }

    public function testModelResourceTypeMapSetsArrayOfModels()
    {
        Apiable::modelResourceTypeMap([Post::class]);
        $this->assertNotEmpty(Apiable::getModelResourceTypeMap());
    }

    public function testJsonApiRenderableReturnsExceptionAsFormatted500ErrorJson()
    {
        $handler = Apiable::jsonApiRenderable(new \Exception('My error'), true);

        $this->assertTrue($handler instanceof Responsable);

        $exceptionAsJson = $handler->toResponse(request());

        $this->assertTrue($exceptionAsJson instanceof JsonResponse);

        $exceptionAsJsonString = $exceptionAsJson->__toString();

        $this->assertStringContainsString('"status":"500"', $exceptionAsJsonString);
        $this->assertStringContainsString('"title":"My error"', $exceptionAsJsonString);
    }

    public function testJsonApiRenderableReturnsExceptionAsFormatted500ErrorJsonWithHiddenDetailsWhenDebugFalse()
    {
        $handler = Apiable::jsonApiRenderable(new \Exception('My error'), false);

        $this->assertTrue($handler instanceof Responsable);

        $exceptionAsJson = $handler->toResponse(request());

        $this->assertTrue($exceptionAsJson instanceof JsonResponse);

        $exceptionAsJsonString = $exceptionAsJson->__toString();

        $this->assertStringContainsString('"status":"500"', $exceptionAsJsonString);
        $this->assertStringContainsString('"title":"Internal server error."', $exceptionAsJsonString);
    }

    public function testJsonApiRenderableReturnsValidationExceptionAsFormatted422ErrorJson()
    {
        $handler = Apiable::jsonApiRenderable(ValidationException::withMessages([
            'email' => ['The email is incorrectly formatted.'],
            'password' => ['The password should have 6 characters or more.'],
        ]));

        $this->assertTrue($handler instanceof Responsable);

        $exceptionAsJson = $handler->toResponse(request());

        $this->assertTrue($exceptionAsJson instanceof JsonResponse);

        $exceptionAsJsonString = $exceptionAsJson->__toString();

        $this->assertStringContainsString('"status":"422"', $exceptionAsJsonString);
        $this->assertStringContainsString('"title":"The email is incorrectly formatted."', $exceptionAsJsonString);
        $this->assertStringContainsString('"source":{"pointer":"email"}', $exceptionAsJsonString);

        $this->assertStringContainsString('"title":"The password should have 6 characters or more."', $exceptionAsJsonString);
        $this->assertStringContainsString('"source":{"pointer":"password"}', $exceptionAsJsonString);
    }
}
