<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\AllowedAppends;
use OpenSoutheners\LaravelApiable\Http\AllowedFields;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;
use OpenSoutheners\LaravelApiable\Tests\Helpers\GeneratesPredictableTestData;

class JsonApiResponseTest extends TestCase
{
    use GeneratesPredictableTestData;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->generateTestData();
    }

    public function testFilteringByNonAllowedAttributeWillGetEverything()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Tag::class)->list();
        });

        $response = $this->get('/?filter[name]=in');

        $response->assertSuccessful();

        $response->assertJsonCount(10, 'data');
    }

    public function testFilteringByAllowedAttributeWillGetFilteredResults()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Tag::class)->allowFilter('name')->list();
        });

        $response = $this->get('/?filter[name]=in');

        $response->assertSuccessful();

        $response->assertJsonCount(4, 'data');
    }

    public function testFilteringOrValuesByAllowedAttributeValue()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status', ['Active', 'Archived']),
                ])->list();
        });

        $response = $this->get('/?filter[status]=Active,Inactive');

        $response->assertJsonCount(2, 'data');
    }

    public function testAllowedFiltersAddedToResponseMeta()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status', ['Active', 'Archived']),
                ])->includeAllowedToResponse()->list();
        });

        $response = $this->get('/?filter[status]=Active,Inactive');

        $response->assertJsonCount(1, 'meta.allowed_filters');
        $response->assertJsonFragment([
            'allowed_filters' => [
                'status' => [
                    '=' => ['Active', 'Archived'],
                ],
            ],
        ]);
    }

    /**
     * @group betterInLaravel916
     */
    public function testFilteringByRelationship()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedInclude::make('author'),
                    AllowedFilter::exact('author.name'),
                ])->list();
        });

        $response = $this->get('/?include=author&filter[author.name]=Ruben');

        $response->assertJsonCount(1, 'data');
    }

    // TODO: Need to do this as unit test to know if query is optimal, guess it is...
    public function testFilteringByTwoDifferentAttributesOfSameRelationship()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedInclude::make('author'),
                    AllowedFilter::exact('author.name'),
                    AllowedFilter::similar('author.email'),
                ])->list();
        });

        $response = $this->get('/?include=author&filter[author.name]=Ruben&filter[author.email]=d8vjork');

        $response->assertJsonCount(1, 'data');
    }

    public function testAddingFieldsAsDbColumns()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedFields::make('client', ['name', 'email_verified_at']),
                ])->list();
        });

        $response = $this->get('/?fields[client]=name');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)->hasAttribute('name');
        });
    }

    public function testAddingFieldsAsModelAppendedAttributes()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ])->list();
        });

        $response = $this->get('/?appends[post]=is_published');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)->hasAttribute('is_published');
        });
    }

    public function testGetOneReturnsJsonApiResourceAsResponse()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ])->getOne(1);
        });

        $response = $this->get('/?appends[post]=is_published');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isResource()->hasAttribute('is_published');
        });
    }
}
