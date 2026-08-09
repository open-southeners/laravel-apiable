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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiableTest extends TestCase
{
    public function test_apiable_facade_is_registered_into_the_container()
    {
        $this->assertTrue($this->app->bound('apiable'));
        $this->assertInstanceOf(Apiable::class, $this->app->make('apiable'));
    }

    public function test_apiable_helper_returns_support_from_container()
    {
        $this->assertTrue(function_exists('apiable'));
        $this->assertInstanceOf(Apiable::class, apiable());
    }

    public function test_to_json_api_returns_empty_json_api_collection_when_invalid_input()
    {
        $this->assertEquals(new JsonApiCollection(Collection::make([])), Apiable::toJsonApi(new \stdClass));
        $this->assertEquals(new JsonApiCollection(Collection::make([])), Apiable::toJsonApi('test'));
    }

    public function test_to_json_api_returns_formatted_json_when_valid_input()
    {
        $firstPost = new Post(['id' => 1, 'title' => 'foo', 'content' => 'bar', 'status' => 'Published']);
        $secondPost = new Post(['id' => 2, 'title' => 'hello', 'content' => 'world', 'status' => 'Published']);

        $this->assertTrue(Apiable::toJsonApi(new Plan) instanceof JsonApiResource);
        $this->assertTrue(Apiable::toJsonApi($firstPost) instanceof JsonApiResource);
        $this->assertTrue(Apiable::toJsonApi(collect([$firstPost, $secondPost])) instanceof JsonApiCollection);
        $this->assertTrue(Apiable::toJsonApi(Post::query()) instanceof JsonApiCollection);
        $this->assertTrue(Apiable::toJsonApi(Post::paginate()) instanceof JsonApiCollection);
    }

    public function test_response_returns_true_when_valid_input()
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

    public function test_get_model_resource_type_map_gets_non_empty_array()
    {
        $this->assertIsArray(Apiable::getModelResourceTypeMap());
        $this->assertNotEmpty(Apiable::getModelResourceTypeMap());
    }

    public function test_model_resource_type_map_sets_replacing_previous_array()
    {
        $this->assertNotEmpty(Apiable::getModelResourceTypeMap());
        Apiable::modelResourceTypeMap([]);
        $this->assertEmpty(Apiable::getModelResourceTypeMap());
    }

    public function test_model_resource_type_map_sets_array_of_models()
    {
        Apiable::modelResourceTypeMap([Post::class]);
        $this->assertNotEmpty(Apiable::getModelResourceTypeMap());
    }

    public function test_handler_can_be_sent_directly_without_calling_to_response_first()
    {
        // Reproduces: "Call to undefined method OpenSoutheners\LaravelApiable\Handler::send()"
        // This happens when Handler is returned from an app's render() method directly
        // instead of via a renderable() callback, causing HandleExceptions to call ->send() on it.
        $handler = Apiable::jsonApiRenderable(new \Exception('My error'), false);

        $this->assertTrue(method_exists($handler, 'send'));

        ob_start();
        $handler->send();
        $output = ob_get_clean();

        $this->assertStringContainsString('"status":"500"', $output);
        $this->assertStringContainsString('"title":"Internal server error."', $output);
    }

    public function test_json_api_renderable_returns_exception_as_formatted500_error_json()
    {
        $handler = Apiable::jsonApiRenderable(new \Exception('My error'), true);

        $this->assertTrue($handler instanceof Responsable);

        $exceptionAsJson = $handler->toResponse(request());

        $this->assertTrue($exceptionAsJson instanceof JsonResponse);

        $exceptionAsJsonString = $exceptionAsJson->__toString();

        $this->assertStringContainsString('"status":"500"', $exceptionAsJsonString);
        $this->assertStringContainsString('"title":"My error"', $exceptionAsJsonString);
    }

    public function test_json_api_renderable_returns_exception_as_formatted500_error_json_with_hidden_details_when_debug_false()
    {
        $handler = Apiable::jsonApiRenderable(new \Exception('My error'), false);

        $this->assertTrue($handler instanceof Responsable);

        $exceptionAsJson = $handler->toResponse(request());

        $this->assertTrue($exceptionAsJson instanceof JsonResponse);

        $exceptionAsJsonString = $exceptionAsJson->__toString();

        $this->assertStringContainsString('"status":"500"', $exceptionAsJsonString);
        $this->assertStringContainsString('"title":"Internal server error."', $exceptionAsJsonString);
    }

    public function test_json_api_renderable_returns_validation_exception_as_formatted422_error_json()
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

    public function test_json_api_renderable_keeps_real_title_for_403_with_debug_false()
    {
        $handler = Apiable::jsonApiRenderable(new AccessDeniedHttpException('This action is unauthorized.'), false);

        $exceptionAsJson = $handler->toResponse(request())->getData(true);

        $this->assertSame('403', $exceptionAsJson['errors'][0]['status']);
        $this->assertSame('This action is unauthorized.', $exceptionAsJson['errors'][0]['title']);
        $this->assertArrayNotHasKey('trace', $exceptionAsJson['errors'][0]);
    }

    public function test_json_api_renderable_keeps_real_title_for_404_with_debug_false()
    {
        $handler = Apiable::jsonApiRenderable(new NotFoundHttpException('Post not found.'), false);

        $exceptionAsJson = $handler->toResponse(request())->getData(true);

        $this->assertSame('404', $exceptionAsJson['errors'][0]['status']);
        $this->assertSame('Post not found.', $exceptionAsJson['errors'][0]['title']);
        $this->assertArrayNotHasKey('trace', $exceptionAsJson['errors'][0]);
    }

    public function test_json_api_renderable_keeps_real_title_for_422_with_debug_false()
    {
        $handler = Apiable::jsonApiRenderable(ValidationException::withMessages([
            'email' => ['The email is incorrectly formatted.'],
        ]), false);

        $exceptionAsJson = $handler->toResponse(request())->getData(true);

        $this->assertSame('422', $exceptionAsJson['errors'][0]['status']);
        $this->assertSame('The email is incorrectly formatted.', $exceptionAsJson['errors'][0]['title']);
    }

    public function test_json_api_renderable_keeps_generic_title_for_500_with_debug_false()
    {
        $handler = Apiable::jsonApiRenderable(new \Exception('Some internal detail leaking a stack frame.'), false);

        $exceptionAsJson = $handler->toResponse(request())->getData(true);

        $this->assertSame('500', $exceptionAsJson['errors'][0]['status']);
        $this->assertSame('Internal server error.', $exceptionAsJson['errors'][0]['title']);
        $this->assertArrayNotHasKey('trace', $exceptionAsJson['errors'][0]);
    }

    public function test_json_api_renderable_shows_real_title_and_trace_for_403_with_debug_true()
    {
        $handler = Apiable::jsonApiRenderable(new AccessDeniedHttpException('This action is unauthorized.'), true);

        $exceptionAsJson = $handler->toResponse(request())->getData(true);

        $this->assertSame('403', $exceptionAsJson['errors'][0]['status']);
        $this->assertSame('This action is unauthorized.', $exceptionAsJson['errors'][0]['title']);
        $this->assertArrayHasKey('trace', $exceptionAsJson['errors'][0]);
    }

    public function test_json_api_renderable_shows_real_title_and_trace_for_500_with_debug_true()
    {
        $handler = Apiable::jsonApiRenderable(new \Exception('My error'), true);

        $exceptionAsJson = $handler->toResponse(request())->getData(true);

        $this->assertSame('500', $exceptionAsJson['errors'][0]['status']);
        $this->assertSame('My error', $exceptionAsJson['errors'][0]['title']);
        $this->assertArrayHasKey('trace', $exceptionAsJson['errors'][0]);
    }
}
