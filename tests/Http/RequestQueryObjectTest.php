<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use OpenSoutheners\LaravelApiable\Http\AllowedAppends;
use OpenSoutheners\LaravelApiable\Http\AllowedFields;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class RequestQueryObjectTest extends TestCase
{
    protected function newRequestQueryObject()
    {
        return new RequestQueryObject(app(Request::class), Post::query());
    }

    public function test_request_query_object_allows_appends_sending_raw()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowAppends('post', ['is_published'])
            ->getAllowedAppends();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['is_published']]), json_encode($allowedAttributes));
    }

    public function test_request_query_object_allows_appends_sending_raw_with_model_class_as_type()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowAppends(Post::class, ['is_published'])
            ->getAllowedAppends();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['is_published']]), json_encode($allowedAttributes));
    }

    public function test_request_query_object_allows_appends_sending_object()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowAppends(AllowedAppends::make('post', ['is_published']))
            ->getAllowedAppends();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['is_published']]), json_encode($allowedAttributes));
    }

    public function test_request_query_object_allows_appends_sending_object_with_model_class_as_type()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowAppends(AllowedAppends::make(Post::class, ['is_published']))
            ->getAllowedAppends();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['is_published']]), json_encode($allowedAttributes));
    }

    public function test_request_query_object_allows_sparse_fieldset_sending_raw()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFields('post', ['created_at'])
            ->getAllowedFields();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['created_at']]), json_encode($allowedAttributes));
    }

    public function test_request_query_object_allows_sparse_fieldset_sending_raw_with_model_class_as_type()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFields(Post::class, ['created_at'])
            ->getAllowedFields();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['created_at']]), json_encode($allowedAttributes));
    }

    public function test_request_query_object_allows_sparse_fieldset_sending_object()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFields(AllowedFields::make('post', ['created_at']))
            ->getAllowedFields();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['created_at']]), json_encode($allowedAttributes));
    }

    public function test_request_query_object_allows_sparse_fieldset_sending_object_with_model_class_as_type()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFields(AllowedFields::make(Post::class, ['created_at']))
            ->getAllowedFields();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['created_at']]), json_encode($allowedAttributes));
    }

    public function test_request_query_object_allows_sorts_sending_raw()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowSort('created_at')
            ->getAllowedSorts();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(
            json_encode(['created_at' => '*']),
            json_encode($allowedAttributes)
        );
    }

    public function test_request_query_object_allows_sorts_sending_object()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowSort(AllowedSort::descendant('created_at'))
            ->getAllowedSorts();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['created_at' => 'desc']), json_encode($allowedAttributes));
    }

    public function test_request_query_object_allows_filters_sending_raw_with_string_value()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFilter('status', 'Active')
            ->getAllowedFilters();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(
            json_encode(['status' => ['operator' => 'like', 'values' => 'Active']]),
            json_encode($allowedAttributes)
        );
    }

    public function test_request_query_object_allows_filters_sending_raw_with_array_of_values()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFilter('status', ['Active', 'Inactive'])
            ->getAllowedFilters();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(
            json_encode(['status' => ['operator' => 'like', 'values' => ['Active', 'Inactive']]]),
            json_encode($allowedAttributes)
        );
    }

    public function test_request_query_object_allows_filters_sending_object()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFilter(AllowedFilter::exact('status'))
            ->getAllowedFilters();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(
            json_encode(['status' => ['operator' => 'equal', 'values' => '*']]),
            json_encode($allowedAttributes)
        );
    }

    public function test_request_query_object_allows_includes_sending_raw()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowInclude('parent')
            ->getAllowedIncludes();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertTrue(empty(array_diff(['parent'], $allowedAttributes)));
    }

    public function test_request_query_object_allows_includes_sending_object()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowInclude(AllowedInclude::make('parent'))
            ->getAllowedIncludes();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertTrue(empty(array_diff(['parent'], $allowedAttributes)));
    }

    public function test_request_query_object_allows_sending_mixed_args()
    {
        $requestQueryObject = $this->newRequestQueryObject()
            ->allows(
                sorts: ['title', ['created_at', AllowedSort::DESCENDANT]],
                fields: [
                    [Post::class, ['title', 'content', 'created_at']],
                    [User::class, ['name', 'email']],
                ],
                includes: ['parent']
            );

        $allowedSorts = $requestQueryObject->getAllowedSorts();

        $this->assertIsArray($allowedSorts);
        $this->assertNotEmpty($allowedSorts);
        $this->assertEquals(
            json_encode([
                'title' => '*',
                'created_at' => 'desc',
            ]),
            json_encode($allowedSorts)
        );

        $allowedFields = $requestQueryObject->getAllowedFields();

        $this->assertIsArray($allowedFields);
        $this->assertNotEmpty($allowedFields);
        $this->assertEquals(
            json_encode(['post' => ['title', 'content', 'created_at'], 'client' => ['name', 'email']]),
            json_encode($allowedFields)
        );

        $allowedIncludes = $requestQueryObject->getAllowedIncludes();

        $this->assertIsArray($allowedIncludes);
        $this->assertNotEmpty($allowedIncludes);
        $this->assertTrue(empty(array_diff(['parent'], $allowedIncludes)));
    }
}
