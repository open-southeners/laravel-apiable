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
use OpenSoutheners\LaravelApiable\Tests\Fixtures\PredictableDataGenerator;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;

class JsonApiResponseTest extends TestCase
{
    /**
     * @var \OpenSoutheners\LaravelApiable\Tests\Fixtures\PredictableDataGenerator
     */
    protected $generator;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->generator = PredictableDataGenerator::generate();
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
            $assert->at(0)->hasAttribute('name');
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

        $response = $this->get('/?fields[post]=is_published');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->at(0)->hasAttribute('is_published');
        });
    }
}
