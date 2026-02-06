<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\AllowedAppends;
use OpenSoutheners\LaravelApiable\Http\AllowedFields;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\DefaultFilter;
use OpenSoutheners\LaravelApiable\Http\DefaultSort;
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
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->generateTestData();
    }

    // ---------------------------------------------------------------
    // Filters – Similar (LIKE)
    // ---------------------------------------------------------------

    public function testFilteringByNonAllowedAttributeWillGetEverything()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Tag::class);
        });

        $response = $this->get('/?filter[name]=in', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();

        $response->assertJsonCount(10, 'data');
    }

    public function testFilteringByAllowedAttributeWillGetFilteredResults()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Tag::class)->allowFilter('name');
        });

        $response = $this->get('/?filter[name]=in', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();

        $response->assertJsonCount(4, 'data');
    }

    public function testFilteringSimilarByTitle()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::similar('title'),
                ]);
        });

        $response = $this->get('/?filter[title]=Hello', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('title', 'Hello world')
        );
    }

    public function testFilteringSimilarMatchesMultipleResults()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::similar('title'),
                ]);
        });

        // "Hola mundo" and "Hello world" both contain "ol" via LIKE %ol%
        $response = $this->get('/?filter[title]=ol', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    public function testFilteringSimilarWithNoMatchReturnsEmpty()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::similar('title'),
                ]);
        });

        $response = $this->get('/?filter[title]=nonexistent_value_xyz', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(0, 'data');
    }

    // ---------------------------------------------------------------
    // Filters – Exact (=)
    // ---------------------------------------------------------------

    public function testFilteringExactByStatus()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status'),
                ]);
        });

        $response = $this->get('/?filter[status]=Active', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    public function testFilteringExactDoesNotMatchPartialValues()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status'),
                ]);
        });

        // "Act" is a partial match for "Active" but exact should not match
        $response = $this->get('/?filter[status]=Act', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(0, 'data');
    }

    public function testFilteringExactWithRestrictedValues()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status', ['Active', 'Archived']),
                ]);
        });

        $response = $this->get('/?filter[status]=Active', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    public function testFilteringOrValuesByAllowedAttributeValueInvalidatesWholeFilter()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status', ['Active', 'Archived']),
                ]);
        });

        $response = $this->get('/?filter[status]=Active,Inactive', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonCount(4, 'data');
    }

    public function testFilteringExactWithOrValues()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status'),
                ]);
        });

        // Comma-separated OR: both Active and Archived posts
        $response = $this->get('/?filter[status]=Active,Archived', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(3, 'data');
    }

    // ---------------------------------------------------------------
    // Filters – Scope
    // ---------------------------------------------------------------

    public function testFilteringByAllowedScope()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::scoped('active'),
                ]);
        });

        $response = $this->get('/?filter[active]=1', ['Accept' => 'application/vnd.api+json']);

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

        $response = $this->get('/?filter[status_scoped]=Active', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonCount(2, 'data');
    }

    public function testFilteringByScopeWithEnforcedNamesAndParameterValue()
    {
        config(['apiable.requests.filters.enforce_scoped_names' => true]);

        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::scoped('status', ['Archived']),
                ]);
        });

        $response = $this->get('/?filter[status_scoped]=Archived', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('title', 'Hello world')
        );
    }

    // ---------------------------------------------------------------
    // Filters – Lower than / Lower or equal than
    // ---------------------------------------------------------------

    public function testFilteringLowerThan()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::lowerThan('author_id'),
                ]);
        });

        // Posts with author_id < 2: post 1 (author_id=1)
        $response = $this->get('/?filter[author_id]=2', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('title', 'My first test')
        );
    }

    public function testFilteringLowerOrEqualThan()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::lowerOrEqualThan('author_id'),
                ]);
        });

        // Posts with author_id <= 2: post 1 (author_id=1) + post 2 (author_id=2)
        $response = $this->get('/?filter[author_id]=2', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    // ---------------------------------------------------------------
    // Filters – Greater than / Greater or equal than
    // ---------------------------------------------------------------

    public function testFilteringGreaterThan()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::greaterThan('author_id'),
                ]);
        });

        // Posts with author_id > 2: post 3 (author_id=3) + post 4 (author_id=3)
        $response = $this->get('/?filter[author_id]=2', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    public function testFilteringGreaterOrEqualThan()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::greaterOrEqualThan('author_id'),
                ]);
        });

        // Posts with author_id >= 2: post 2 (author_id=2) + post 3 + post 4 (author_id=3)
        $response = $this->get('/?filter[author_id]=2', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(3, 'data');
    }

    // ---------------------------------------------------------------
    // Filters – Relationship
    // ---------------------------------------------------------------

    public function testFilteringByRelationship()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedInclude::make('author'),
                    AllowedFilter::exact('author.name'),
                ]);
        });

        $response = $this->get('/?include=author&filter[author.name]=Ruben', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonCount(1, 'data');
    }

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

        $response = $this->get('/?include=author&filter[author.name]=Ruben&filter[author.email]=d8vjork', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonCount(1, 'data');
    }

    public function testFilteringSimilarByRelationshipAttribute()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedInclude::make('author'),
                    AllowedFilter::similar('author.name'),
                ]);
        });

        // Both "Ruben" users: author_id=2 (post 2) and no post for author_id=5
        // "Ruben" matched via LIKE: author_id=2 -> post 2 "Hello world"
        $response = $this->get('/?include=author&filter[author.name]=Rub', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
    }

    // ---------------------------------------------------------------
    // Filters – Default filter
    // ---------------------------------------------------------------

    public function testDefaultFilterAppliedWhenNoUserFilterSent()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status'),
                ])
                ->applyDefaultFilter('status', AllowedFilter::EXACT, 'Active');
        });

        // No filter in query string → default exact filter status=Active applied
        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    public function testDefaultFilterNotAppliedWhenUserFilterSent()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status'),
                ])
                ->applyDefaultFilter('status', AllowedFilter::EXACT, 'Active');
        });

        // User sends filter → default is ignored
        $response = $this->get('/?filter[status]=Archived', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
    }

    // ---------------------------------------------------------------
    // Filters – Allowed to response meta
    // ---------------------------------------------------------------

    public function testAllowedFiltersAddedToResponseMeta()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status', ['Active', 'Archived']),
                ])->includeAllowedToResponse();
        });

        $response = $this->get('/?filter[status]=Active,Inactive', ['Accept' => 'application/vnd.api+json']);

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

        $response = $this->get('/?filter[status]=Active,Inactive', ['Accept' => 'application/vnd.api+json']);

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

    // ---------------------------------------------------------------
    // Sorts – Ascendant / Descendant
    // ---------------------------------------------------------------

    public function testSortingFieldsAsDescendant()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedSort::descendant('name'),
                ]);
        });

        $response = $this->get('/?sort=-name', ['Accept' => 'application/vnd.api+json']);

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

        $response = $this->get('/?sort=name', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)->hasAttribute('name', 'Aysha');
        });
    }

    public function testSortingBothDirectionsAllowed()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedSort::make('name'),
                ]);
        });

        // Ascending
        $responseAsc = $this->get('/?sort=name', ['Accept' => 'application/vnd.api+json']);

        $responseAsc->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('name', 'Aysha')
        );

        // Descending
        $responseDesc = $this->get('/?sort=-name', ['Accept' => 'application/vnd.api+json']);

        $responseDesc->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('name', 'Ruben')
        );
    }

    // ---------------------------------------------------------------
    // Sorts – Relationship (BelongsToMany)
    // ---------------------------------------------------------------

    public function testSortingBelongsToManyRelationshipFieldAsAscendant()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::query()->withCount('tags'))
                ->allowing([
                    AllowedSort::ascendant('tags.name'),
                ]);
        });

        $response = $this->get('/?sort=tags.name', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection();

            $assert->at(0)->hasAttribute('title', 'Hola mundo');
            $assert->at(1)->hasAttribute('title', 'My first test');
            $assert->hasAttribute('tags_count');
        });
    }

    public function testSortingBelongsToManyRelationshipFieldAsDescendant()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::query()->withCount('tags'))
                ->allowing([
                    AllowedSort::descendant('tags.name'),
                ]);
        });

        $response = $this->get('/?sort=-tags.name', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection();

            $assert->at(0)->hasAttribute('title', 'Hello world');
            $assert->at(1)->hasAttribute('title', 'Y esto en español');
            $assert->hasAttribute('tags_count');
        });
    }

    // ---------------------------------------------------------------
    // Sorts – Relationship (BelongsTo)
    // ---------------------------------------------------------------

    public function testSortingBelongsToRelationshipFieldAsAscendant()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedSort::ascendant('author.name'),
                ]);
        });

        $response = $this->get('/?sort=author.name', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection();

            $assert->at(0)->hasAttribute('title', 'My first test');
            $assert->at(1)->hasAttribute('title', 'Y esto en español');
        });
    }

    public function testSortingBelongsToRelationshipFieldAsDescendant()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedSort::descendant('author.name'),
                ]);
        });

        $response = $this->get('/?sort=-author.name', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection();

            $assert->at(0)->hasAttribute('title', 'Hello world');
            $assert->at(1)->hasAttribute('title', 'Y esto en español');
        });
    }

    // ---------------------------------------------------------------
    // Sorts – Default sort
    // ---------------------------------------------------------------

    public function testDefaultSortAppliedWhenNoUserSortSent()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedSort::make('name'),
                ])
                ->applyDefaultSort(DefaultSort::descendant('name'));
        });

        // No sort in query string → default desc name applied
        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('name', 'Ruben')
        );
    }

    public function testDefaultSortNotAppliedWhenUserSortSent()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedSort::make('name'),
                ])
                ->applyDefaultSort(DefaultSort::descendant('name'));
        });

        // User sends ascending sort → default is ignored
        $response = $this->get('/?sort=name', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('name', 'Aysha')
        );
    }

    public function testAllowedSortsAddedToResponseMeta()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedSort::ascendant('title'),
                ])->includeAllowedToResponse();
        });

        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonFragment([
            'allowed_sorts' => [
                'title' => 'asc',
            ],
        ]);
    }

    // ---------------------------------------------------------------
    // Includes
    // ---------------------------------------------------------------

    public function testIncludeRelationship()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedInclude::make('author'),
                ]);
        });

        $response = $this->get('/?include=author', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'type', 'attributes', 'relationships'],
            ],
            'included',
        ]);
    }

    public function testIncludeMultipleRelationships()
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
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'type', 'attributes', 'relationships'],
            ],
            'included',
        ]);

        $data = $response->json('included');
        $types = array_unique(array_column($data, 'type'));
        sort($types);

        $this->assertContains('label', $types);
    }

    public function testIncludeCountAsAttribute()
    {
        Route::get('/', function () {
            return response()->json(
                JsonApiResponse::from(Post::class)->allowInclude(['tags_count'])
            );
        });

        $response = $this->get('/?include=tags_count', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->hasAttribute('tags_count')
        );
    }

    public function testNonAllowedIncludeIsIgnored()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedInclude::make('tags'),
                ]);
        });

        // "author" is not allowed, only "tags"
        $response = $this->get('/?include=author', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonMissing(['type' => 'client']);
    }

    public function testIncludeWithArraySyntax()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedInclude::make(['author', 'tags']),
                ]);
        });

        $response = $this->get('/?include=author,tags', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'type', 'attributes'],
            ],
        ]);
    }

    // ---------------------------------------------------------------
    // Appends
    // ---------------------------------------------------------------

    public function testAddingFieldsAsModelAppendedAttributes()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ]);
        });

        $response = $this->get('/?appends[post]=is_published', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)->hasAttribute('is_published');
        });
    }

    public function testAppendsNotAddedWithoutQueryParam()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ]);
        });

        // No appends in query → attribute should not be present
        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)->hasNotAttribute('is_published');
        });
    }

    public function testNonAllowedAppendIsIgnored()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ]);
        });

        // "nonexistent" is not an allowed append
        $response = $this->get('/?appends[post]=nonexistent', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasNotAttribute('nonexistent')
        );
    }

    public function testAppendsOnSingleResource()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::whereKey(1))
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ])->gettingOne();
        });

        $response = $this->get('/?appends[post]=is_published', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isResource()->hasAttribute('is_published');
        });
    }

    public function testAppendsWithMultipleAttributes()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedAppends::make('post', ['is_published']),
                ]);
        });

        $response = $this->get('/?appends[post]=is_published', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('is_published', true)
        );
    }

    // ---------------------------------------------------------------
    // Fields (Sparse fieldsets)
    // ---------------------------------------------------------------

    public function testSparseFieldset()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedFields::make('client', ['name', 'email']),
                ]);
        });

        $response = $this->get('/?fields[client]=name', ['Accept' => 'application/vnd.api+json']);

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

        $response = $this->get('/?fields[client]=name,email_verified_at', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isCollection()->at(0)
                ->hasAttribute('name')
                ->hasNotAttribute('email_verified_at');
        });
    }

    public function testSparseFieldsetWithMultipleAllowedFields()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedFields::make('client', ['name', 'email']),
                ]);
        });

        $response = $this->get('/?fields[client]=name,email', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)
            ->hasAttribute('name')
            ->hasAttribute('email')
        );
    }

    public function testSparseFieldsetWithNoFieldsQueryReturnsAll()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(User::class)
                ->allowing([
                    AllowedFields::make('client', ['name', 'email']),
                ]);
        });

        // No fields query param → all visible attributes returned
        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)
            ->hasAttribute('name')
            ->hasAttribute('email')
        );
    }

    // ---------------------------------------------------------------
    // Search
    // ---------------------------------------------------------------

    public function testListPerformingFulltextSearch()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowSearch();
        });

        $response = $this->get('/?q=español', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->hasSize(1)->hasAttribute('title', 'Y esto en español');
        });
    }

    // ---------------------------------------------------------------
    // Getting one result
    // ---------------------------------------------------------------

    public function testGetOneReturnsJsonApiResourceAsResponse()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::whereKey(1))
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ])->gettingOne();
        });

        $response = $this->get('/?appends[post]=is_published', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->isResource()->hasAttribute('is_published');
        });
    }

    // ---------------------------------------------------------------
    // Combined: filters + sorts + includes + fields + appends
    // ---------------------------------------------------------------

    public function testCombinedFiltersAndSorts()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status'),
                    AllowedSort::make('title'),
                ]);
        });

        $response = $this->get('/?filter[status]=Active&sort=title', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('title', 'My first test')
        );
    }

    public function testCombinedFiltersAndIncludes()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status'),
                    AllowedInclude::make('author'),
                ]);
        });

        $response = $this->get('/?filter[status]=Active&include=author', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure(['included']);
    }

    public function testCombinedAllQueryFeatures()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status'),
                    AllowedSort::ascendant('title'),
                    AllowedInclude::make('tags'),
                    AllowedAppends::make('post', 'is_published'),
                    AllowedFields::make('post', ['title', 'status', 'content']),
                ]);
        });

        $response = $this->get('/?filter[status]=Active&sort=title&include=tags&appends[post]=is_published&fields[post]=title', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)
            ->hasAttribute('title', 'My first test')
            ->hasAttribute('is_published')
        );
    }

    // ---------------------------------------------------------------
    // Response as array (Inertia-like)
    // ---------------------------------------------------------------

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

        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

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

    // ---------------------------------------------------------------
    // withCount via modified query
    // ---------------------------------------------------------------

    public function testResponseWithModifiedQueryWithCountMethodGetsRelationshipsCountsAsAttribute()
    {
        Route::get('/', function () {
            return response()->json(
                JsonApiResponse::from(Post::query()->withCount('tags'))
            );
        });

        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->hasAttribute('tags_count')
        );
    }

    public function testResponseWithAllowedIncludedEndsWithCountGetsRelationshipCountAsAttribute()
    {
        Route::get('/', function () {
            return response()->json(
                JsonApiResponse::from(Post::class)->allowInclude(['tags_count'])
            );
        });

        $response = $this->get('/?include=tags_count', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->hasAttribute('tags_count')
        );
    }
}
