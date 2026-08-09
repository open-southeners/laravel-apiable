<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\AllowedAppends;
use OpenSoutheners\LaravelApiable\Http\AllowedFields;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
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
    protected function setUp(): void
    {
        parent::setUp();

        $this->generateTestData();
    }

    // ---------------------------------------------------------------
    // Filters – Similar (LIKE)
    // ---------------------------------------------------------------

    public function test_filtering_by_non_allowed_attribute_will_get_everything()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Tag::class);
        });

        $response = $this->get('/?filter[name]=in', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();

        $response->assertJsonCount(10, 'data');
    }

    public function test_filtering_by_allowed_attribute_will_get_filtered_results()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Tag::class)->allowFilter('name');
        });

        $response = $this->get('/?filter[name]=in', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();

        $response->assertJsonCount(4, 'data');
    }

    public function test_filtering_similar_by_title()
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

    public function test_filtering_similar_matches_multiple_results()
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

    public function test_filtering_similar_with_no_match_returns_empty()
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

    public function test_filtering_exact_by_status()
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

    public function test_filtering_exact_does_not_match_partial_values()
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

    public function test_filtering_exact_with_restricted_values()
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

    public function test_filtering_or_values_drops_the_invalid_value_and_keeps_the_valid_one()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status', ['Active', 'Archived']),
                ]);
        });

        // "Inactive" isn't an allowed value: it's dropped, "Active" still applies on its own.
        $response = $this->get('/?filter[status]=Active,Inactive', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonCount(2, 'data');
    }

    public function test_filtering_or_values_with_all_values_disallowed_falls_back_to_default()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::exact('status', ['Active', 'Archived']),
                ]);
        });

        // Neither "Inactive" nor "Deleted" are allowed values, so nothing survives validation
        // and the filter falls back to no filtering (no default filter registered here).
        $response = $this->get('/?filter[status]=Inactive,Deleted', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonCount(4, 'data');
    }

    public function test_filtering_exact_with_or_values()
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

    public function test_filtering_by_allowed_scope()
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

    public function test_filtering_by_allowed_scope_using_enforced_names()
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

    public function test_filtering_by_scope_with_enforced_names_and_parameter_value()
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

    public function test_filtering_by_scope_with_multiple_named_arguments()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::scoped('withStatuses', '*'),
                ]);
        });

        $response = $this->get('/?filter[withStatuses]=Active&filter[withStatuses]=Archived', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(3, 'data');
    }

    public function test_filtering_by_scope_with_named_arguments_using_the_default_registration()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    // No explicit '*' pattern: named scope arguments validate against an
                    // unrestricted pattern by default instead of the truthy '1' default.
                    AllowedFilter::scoped('withStatuses'),
                ]);
        });

        $response = $this->get('/?filter[withStatuses][status1]=Active&filter[withStatuses][status2]=Archived', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(3, 'data');
    }

    // ---------------------------------------------------------------
    // Filters – Lower than / Lower or equal than
    // ---------------------------------------------------------------

    public function test_filtering_lower_than()
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

    public function test_filtering_lower_or_equal_than()
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

    public function test_filtering_greater_than()
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

    public function test_filtering_greater_or_equal_than()
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
    // Filters – Multiple operators on the same attribute (range filters)
    // ---------------------------------------------------------------

    public function test_filtering_by_two_operators_on_the_same_attribute_applies_both_as_a_range()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::greaterOrEqualThan('author_id'),
                    AllowedFilter::lowerOrEqualThan('author_id'),
                ]);
        });

        // Posts with author_id between 2 and 3: post 2 (2), post 3 (3), post 4 (3)
        $response = $this->get('/?filter[author_id][gte]=2&filter[author_id][lte]=3', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(3, 'data');
    }

    public function test_filtering_by_two_operators_narrows_down_to_a_single_result()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::greaterThan('author_id'),
                    AllowedFilter::lowerThan('author_id'),
                ]);
        });

        // Posts with author_id strictly between 1 and 3: only post 2 (author_id=2)
        $response = $this->get('/?filter[author_id][gt]=1&filter[author_id][lt]=3', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('title', 'Hello world')
        );
    }

    public function test_filtering_by_attribute_with_multiple_operators_using_plain_filter_uses_the_first_registered_operator()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::greaterOrEqualThan('author_id'),
                    AllowedFilter::lowerOrEqualThan('author_id'),
                ]);
        });

        // No operator key sent: falls back to the first-registered operator (gte).
        // Posts with author_id >= 2: post 2 (2), post 3 (3), post 4 (3)
        $response = $this->get('/?filter[author_id]=2', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(3, 'data');
    }

    public function test_filtering_by_attribute_with_multiple_operators_using_an_unregistered_operator_key_is_dropped()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedFilter::greaterOrEqualThan('author_id'),
                ]);
        });

        // "lte" was never registered for author_id, so it's silently dropped (no filtering).
        $response = $this->get('/?filter[author_id][lte]=2', ['Accept' => 'application/vnd.api+json']);

        $response->assertSuccessful();
        $response->assertJsonCount(4, 'data');
    }

    // ---------------------------------------------------------------
    // Filters – Relationship
    // ---------------------------------------------------------------

    public function test_filtering_by_relationship()
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

    public function test_filtering_by_two_different_attributes_of_same_relationship()
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

    public function test_filtering_similar_by_relationship_attribute()
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

    public function test_default_filter_applied_when_no_user_filter_sent()
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

    public function test_default_filter_not_applied_when_user_filter_sent()
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

    public function test_allowed_filters_added_to_response_meta()
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

    public function test_allowed_filters_added_to_response_meta_through_config()
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

    public function test_sorting_fields_as_descendant()
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

    public function test_sorting_fields_as_ascendant()
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

    public function test_sorting_both_directions_allowed()
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

    public function test_sorting_belongs_to_many_relationship_field_as_ascendant()
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
            $assert->at(0)->hasAttribute('tags_count');
        });
    }

    public function test_sorting_belongs_to_many_relationship_field_as_descendant()
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
            $assert->at(0)->hasAttribute('tags_count');
        });
    }

    // ---------------------------------------------------------------
    // Sorts – Relationship (BelongsTo)
    // ---------------------------------------------------------------

    public function test_sorting_belongs_to_relationship_field_as_ascendant()
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

    public function test_sorting_belongs_to_relationship_field_as_descendant()
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

    public function test_default_sort_applied_when_no_user_sort_sent()
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

    public function test_default_sort_not_applied_when_user_sort_sent()
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

    public function test_allowed_sorts_added_to_response_meta()
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

    public function test_include_relationship()
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

    public function test_include_multiple_relationships()
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

    public function test_include_count_as_attribute()
    {
        Route::get('/', function () {
            return response()->json(
                JsonApiResponse::from(Post::class)->allowInclude(['tags_count'])
            );
        });

        $response = $this->get('/?include=tags_count', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('tags_count')
        );
    }

    public function test_non_allowed_include_is_ignored()
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

    public function test_include_with_array_syntax()
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

    public function test_adding_fields_as_model_appended_attributes()
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

    public function test_appends_not_added_without_query_param()
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

    public function test_non_allowed_append_is_ignored()
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

    public function test_non_allowed_append_returns_400_when_validate_params_is_enabled()
    {
        config(['apiable.requests.validate_params' => true]);

        $this->withExceptionHandling();

        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowing([
                    AllowedAppends::make('post', 'is_published'),
                ]);
        });

        // "nonexistent" is not an allowed append
        $response = $this->get('/?appends[post]=nonexistent', ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(400);
    }

    public function test_appends_on_single_resource()
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

    public function test_appends_with_multiple_attributes()
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

    public function test_sparse_fieldset()
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

    public function test_sparse_fieldset_returning_only_allowed_columns()
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

    public function test_sparse_fieldset_with_multiple_allowed_fields()
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

    public function test_sparse_fieldset_with_no_fields_query_returns_all()
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

    public function test_list_performing_fulltext_search()
    {
        Route::get('/', function () {
            return JsonApiResponse::from(Post::class)
                ->allowSearch();
        });

        $response = $this->get('/?q=español', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->hasSize(1)->at(0)->hasAttribute('title', 'Y esto en español');
        });
    }

    // ---------------------------------------------------------------
    // Getting one result
    // ---------------------------------------------------------------

    public function test_get_one_returns_json_api_resource_as_response()
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

    public function test_combined_filters_and_sorts()
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

    public function test_combined_filters_and_includes()
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

    public function test_combined_all_query_features()
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

    public function test_response_as_array_gets_all_content()
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

    public function test_response_with_modified_query_with_count_method_gets_relationships_counts_as_attribute()
    {
        Route::get('/', function () {
            return response()->json(
                JsonApiResponse::from(Post::query()->withCount('tags'))
            );
        });

        $response = $this->get('/', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('tags_count')
        );
    }

    public function test_response_with_allowed_included_ends_with_count_gets_relationship_count_as_attribute()
    {
        Route::get('/', function () {
            return response()->json(
                JsonApiResponse::from(Post::class)->allowInclude(['tags_count'])
            );
        });

        $response = $this->get('/?include=tags_count', ['Accept' => 'application/vnd.api+json']);

        $response->assertJsonApi(fn (AssertableJsonApi $assert) => $assert
            ->isCollection()
            ->at(0)->hasAttribute('tags_count')
        );
    }
}
