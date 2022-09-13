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

    public function testRequestQueryObjectAllowsAppendsSendingRaw()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowAppends('post', ['is_published'])
            ->getAllowedAppends();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['is_published']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsAppendsSendingRawWithModelClassAsType()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowAppends(Post::class, ['is_published'])
            ->getAllowedAppends();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['is_published']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsAppendsSendingObject()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowAppends(AllowedAppends::make('post', ['is_published']))
            ->getAllowedAppends();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['is_published']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsAppendsSendingObjectWithModelClassAsType()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowAppends(AllowedAppends::make(Post::class, ['is_published']))
            ->getAllowedAppends();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['is_published']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsSparseFieldsetSendingRaw()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFields('post', ['created_at'])
            ->getAllowedFields();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['created_at']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsSparseFieldsetSendingRawWithModelClassAsType()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFields(Post::class, ['created_at'])
            ->getAllowedFields();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['created_at']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsSparseFieldsetSendingObject()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFields(AllowedFields::make('post', ['created_at']))
            ->getAllowedFields();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['created_at']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsSparseFieldsetSendingObjectWithModelClassAsType()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFields(AllowedFields::make(Post::class, ['created_at']))
            ->getAllowedFields();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['post' => ['created_at']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsSortsSendingRaw()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowSort('created_at')
            ->getAllowedSorts();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['created_at' => '*']), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsSortsSendingObject()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowSort(AllowedSort::descendant('created_at'))
            ->getAllowedSorts();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['created_at' => 'desc']), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsFiltersSendingRawWithStringValue()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFilter('status', 'Active')
            ->getAllowedFilters();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['status' => ['like' => 'Active']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsFiltersSendingRawWithArrayOfValues()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFilter('status', ['Active', 'Inactive'])
            ->getAllowedFilters();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['status' => ['like' => ['Active', 'Inactive']]]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsFiltersSendingObject()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowFilter(AllowedFilter::exact('status'))
            ->getAllowedFilters();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertEquals(json_encode(['status' => ['=' => '*']]), json_encode($allowedAttributes));
    }

    public function testRequestQueryObjectAllowsIncludesSendingRaw()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowInclude('parent')
            ->getAllowedIncludes();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertTrue(empty(array_diff(['parent'], $allowedAttributes)));
    }

    public function testRequestQueryObjectAllowsIncludesSendingObject()
    {
        $allowedAttributes = $this->newRequestQueryObject()
            ->allowInclude(AllowedInclude::make('parent'))
            ->getAllowedIncludes();

        $this->assertIsArray($allowedAttributes);
        $this->assertNotEmpty($allowedAttributes);
        $this->assertTrue(empty(array_diff(['parent'], $allowedAttributes)));
    }

    public function testRequestQueryObjectAllowsSendingMixedArgs()
    {
        $requestQueryObject = $this->newRequestQueryObject()
            ->allows(
                sorts: ['title', ['created_at', 'desc']],
                fields: [
                    [Post::class, ['title', 'content', 'created_at']],
                    [User::class, ['name', 'email']],
                ]
            );

        $allowedSorts = $requestQueryObject->getAllowedSorts();

        $this->assertIsArray($allowedSorts);
        $this->assertNotEmpty($allowedSorts);
        $this->assertEquals(
            json_encode(['title' => '*', 'created_at' => 'desc']),
            json_encode($allowedSorts)
        );

        $allowedFields = $requestQueryObject->getAllowedFields();

        $this->assertIsArray($allowedFields);
        $this->assertNotEmpty($allowedFields);
        $this->assertEquals(
            json_encode(['post' => ['title', 'content', 'created_at'], 'client' => ['name', 'email']]),
            json_encode($allowedFields)
        );
    }
}
