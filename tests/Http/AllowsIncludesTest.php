<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;
use OpenSoutheners\LaravelApiable\Http\ApplyIncludesToQuery;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Helpers\GeneratesPredictableTestData;
use OpenSoutheners\LaravelApiable\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AllowsIncludesTest extends TestCase
{
    use GeneratesPredictableTestData;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->generateTestData();
    }

    protected function newRequestQueryObject(string $include = ''): RequestQueryObject
    {
        $request = Request::create('/', 'GET', $include !== '' ? ['include' => $include] : []);

        return (new RequestQueryObject($request))->setQuery(Post::query());
    }

    // ---------------------------------------------------------------
    // allowInclude() accumulation (object-cast bug)
    // ---------------------------------------------------------------

    public function test_allow_include_accumulates_multiple_allowed_include_objects()
    {
        $allowedIncludes = $this->newRequestQueryObject()
            ->allowInclude(AllowedInclude::make('author'))
            ->allowInclude(AllowedInclude::make('tags'))
            ->getAllowedIncludes();

        $this->assertTrue(empty(array_diff(['author', 'tags'], $allowedIncludes)));
    }

    public function test_allowing_with_multiple_allowed_include_objects_accumulates()
    {
        // The exact shape reported by rezero: allowing() with multiple AllowedInclude
        // objects used to keep only the last one registered.
        $allowedIncludes = $this->newRequestQueryObject()
            ->allowing([
                AllowedInclude::make('author'),
                AllowedInclude::make('tags'),
            ])
            ->getAllowedIncludes();

        $this->assertTrue(empty(array_diff(['author', 'tags'], $allowedIncludes)));
    }

    public function test_allow_include_accumulates_mixed_strings_and_objects()
    {
        $allowedIncludes = $this->newRequestQueryObject()
            ->allowInclude('parent')
            ->allowInclude(AllowedInclude::make('author'))
            ->allowInclude(['tags'])
            ->getAllowedIncludes();

        $this->assertTrue(empty(array_diff(['parent', 'author', 'tags'], $allowedIncludes)));
    }

    public function test_allow_include_with_count_suffix_still_works_when_accumulated()
    {
        $allowedIncludes = $this->newRequestQueryObject()
            ->allowInclude(AllowedInclude::make('author'))
            ->allowInclude(AllowedInclude::make('tags_count'))
            ->getAllowedIncludes();

        $this->assertTrue(empty(array_diff(['author', 'tags_count'], $allowedIncludes)));
    }

    public function test_multiple_allowed_include_objects_all_take_effect_in_response()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedInclude::make('author'),
                    AllowedInclude::make('tags'),
                ]);
        });

        $response = $this->get('/?include=author,tags', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();

        $includedTypes = array_unique(array_column($response->json('included'), 'type'));

        $this->assertContains('client', $includedTypes);
        $this->assertContains('label', $includedTypes);
    }

    // ---------------------------------------------------------------
    // max_include_depth
    // ---------------------------------------------------------------

    public function test_include_beyond_max_depth_is_dropped_while_shallower_include_still_applies()
    {
        $requestQueryObject = $this->newRequestQueryObject('parent.parent,parent.parent.parent.parent')
            ->allowInclude('parent.parent')
            ->allowInclude('parent.parent.parent.parent');

        $allowedIncludes = array_values($requestQueryObject->userAllowedIncludes());

        $this->assertEquals(['parent.parent'], $allowedIncludes);
    }

    public function test_include_at_max_depth_is_allowed()
    {
        $requestQueryObject = $this->newRequestQueryObject('parent.parent.parent')
            ->allowInclude('parent.parent.parent');

        $allowedIncludes = array_values($requestQueryObject->userAllowedIncludes());

        $this->assertEquals(['parent.parent.parent'], $allowedIncludes);
    }

    public function test_max_include_depth_config_override_is_respected()
    {
        config(['apiable.responses.max_include_depth' => 5]);

        $requestQueryObject = $this->newRequestQueryObject('parent.parent.parent.parent')
            ->allowInclude('parent.parent.parent.parent');

        $allowedIncludes = array_values($requestQueryObject->userAllowedIncludes());

        $this->assertEquals(['parent.parent.parent.parent'], $allowedIncludes);
    }

    public function test_include_beyond_max_depth_throws_http_exception_when_validate_params_enabled()
    {
        config(['apiable.requests.validate_params' => true]);

        $requestQueryObject = $this->newRequestQueryObject('parent.parent.parent.parent')
            ->allowInclude('parent.parent.parent.parent');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('exceeds maximum include depth');

        $requestQueryObject->userAllowedIncludes();
    }

    public function test_disallowed_include_throws_http_exception_with_400_status_when_validate_params_is_enabled()
    {
        config(['apiable.requests.validate_params' => true]);

        $requestQueryObject = $this->newRequestQueryObject('tags')
            ->allowInclude('author');

        try {
            $requestQueryObject->userAllowedIncludes();
            $this->fail('Expected an HttpException to be thrown.');
        } catch (HttpException $exception) {
            $this->assertSame(400, $exception->getStatusCode());
        }
    }

    public function test_count_suffix_include_is_not_affected_by_depth_check()
    {
        $requestQueryObject = $this->newRequestQueryObject('tags_count')
            ->allowInclude('tags_count');

        $allowedIncludes = array_values($requestQueryObject->userAllowedIncludes());

        $this->assertEquals(['tags_count'], $allowedIncludes);
    }

    public function test_apply_includes_to_query_eager_loads_only_includes_within_max_depth()
    {
        $requestQueryObject = $this->newRequestQueryObject('parent.parent,parent.parent.parent.parent')
            ->allowInclude('parent.parent')
            ->allowInclude('parent.parent.parent.parent');

        $query = (new ApplyIncludesToQuery)->from(
            $requestQueryObject,
            fn (RequestQueryObject $requestQueryObject) => $requestQueryObject->query
        );

        $eagerLoads = array_keys($query->getEagerLoads());

        $this->assertContains('parent.parent', $eagerLoads);
        $this->assertNotContains('parent.parent.parent.parent', $eagerLoads);
    }
}
