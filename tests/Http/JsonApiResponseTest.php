<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\AllowedAppends;
use OpenSoutheners\LaravelApiable\Http\AllowedFields;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;
use OpenSoutheners\LaravelApiable\Tests\Helpers\GeneratesPredictableTestData;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

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
            return JsonApiResponse::from(Tag::class);
        });

        $response = $this->get('/?filter[name]=in');

        $response->assertSuccessful();

        $response->assertJsonCount(10, 'data');
    }

    public function testFilteringByAllowedAttributeWillGetFilteredResults()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Tag::class)->allowFilter('name');
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
                ]);
        });

        $response = $this->get('/?filter[status]=Active,Inactive');

        $response->assertJsonCount(2, 'data');
    }

    public function testFilteringByAllowedScope()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::scoped('active'),
                ]);
        });

        $response = $this->get('/?filter[active]=1');

        $response->assertJsonCount(2, 'data');
    }

    public function testFilteringByAllowedScopeUsingEnforcedNames()
    {
        config(['apiable.requests.filters.enforce_scoped_names' => true]);

        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::scoped('status', ['Active']),
                ]);
        });

        $response = $this->get('/?filter[status_scoped]=Active');

        $response->assertJsonCount(2, 'data');
    }

    public function testAllowedFiltersAddedToResponseMeta()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status', ['Active', 'Archived']),
                ])->includeAllowedToResponse();
        });

        $response = $this->get('/?filter[status]=Active,Inactive');

        $response->assertJsonCount(1, 'meta.allowed_filters');
        $response->assertJsonFragment([
            'allowed_filters' => [
                'status' => [
                    'operator' => 'equal',
                    'values' => ['Active', 'Archived'],
                ],
            ],
        ]);
    }

    public function testAllowedFiltersAddedToResponseMetaThroughConfig()
    {
        config(['apiable.responses.include_allowed' => true]);

        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status', ['Active', 'Archived']),
                ]);
        });

        $response = $this->get('/?filter[status]=Active,Inactive');

        $response->assertJsonCount(1, 'meta.allowed_filters');
        $response->assertJsonFragment([
            'allowed_filters' => [
                'status' => [
                    'operator' => 'equal',
                    'values' => ['Active', 'Archived'],
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
                ]);
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
                ]);
        });

        $response = $this->get('/?include=author&filter[author.name]=Ruben&filter[author.email]=d8vjork');

        $response->assertJsonCount(1, 'data');
    }

    public function testSparseFieldset()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedFields::make('client', ['name', 'email']),
                ]);
        });

        $response = $this->get('/?fields[client]=name');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)->hasAttribute('name');
        });
    }

    public function testSparseFieldsetReturningOnlyAllowedColumns()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedFields::make('client', ['name', 'email']),
                ]);
        });

        $response = $this->get('/?fields[client]=name,email_verified_at');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)
                ->hasAttribute('name')
                ->hasNotAttribute('email_verified_at');
        });
    }

    public function testSortingFieldsAsDescendant()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedSort::descendant('name'),
                ]);
        });

        $response = $this->get('/?sort=-name');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)->hasAttribute('name', 'Ruben');
        });
    }

    public function testSortingFieldsAsAscendant()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedSort::ascendant('name'),
                ]);
        });

        $response = $this->getJson('/?sort=name');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)->hasAttribute('name', 'Aysha');
        });
    }

    public function testAddingFieldsAsModelAppendedAttributes()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ]);
        });

        $response = $this->get('/?appends[post]=is_published');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)->hasAttribute('is_published');
        });
    }

    public function testGetOneReturnsJsonApiResourceAsResponse()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::whereKey(1))
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ])->gettingOne();
        });

        $response = $this->get('/?appends[post]=is_published');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isResource()->hasAttribute('is_published');
        });
    }

    public function testListPerformingFulltextSearch()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowSearch();
        });

        $response = $this->get('/?q=español');

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->hasSize(1)->hasAttribute('title', 'Y esto en español');
        });
    }

    public function testResponseAsArrayGetsAllContent()
    {
        // Yeah, we need to enforce this macro to "fake" Inertia so force toArray response behaviour
        Request::macro('inertia', fn () => true);

        config(['apiable.responses.include_allowed' => true]);

        Route::get('/', function () {
            return response()->json(JsonApiResponse::from(Post::with('tags'))->allowing([
                AllowedFilter::exact('status', ['Active', 'Archived']),
            ]));
        });

        $response = $this->getJson('/');

        $response->assertJsonCount(4, 'data');
        $response->assertJsonCount(1, 'meta.allowed_filters');
        $response->assertJsonFragment([
            'allowed_filters' => [
                'status' => [
                    'operator' => 'equal',
                    'values' => ['Active', 'Archived'],
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'id' => '1',
            'type' => 'post',
            'relationships' => [
                'tags' => [
                    'data' => [
                        [
                            'id' => '1',
                            'type' => 'label',
                        ],
                        [
                            'id' => '3',
                            'type' => 'label',
                        ],
                        [
                            'id' => '4',
                            'type' => 'label',
                        ],
                    ],
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'id' => '1',
            'type' => 'post',
        ]);
    }
}
