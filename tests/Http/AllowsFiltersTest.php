<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AllowsFiltersTest extends TestCase
{
    protected function newRequestQueryObject(array $query = []): RequestQueryObject
    {
        $request = Request::create('/', 'GET', $query);

        return (new RequestQueryObject($request))->setQuery(Post::query());
    }

    // ---------------------------------------------------------------
    // allowFilter() merging multiple operators on the same attribute
    // ---------------------------------------------------------------

    public function test_allow_filter_with_two_operators_on_the_same_attribute_merges_into_an_operator_map()
    {
        $allowedFilters = $this->newRequestQueryObject()
            ->allowFilter(AllowedFilter::greaterOrEqualThan('due_at'))
            ->allowFilter(AllowedFilter::lowerOrEqualThan('due_at'))
            ->getAllowedFilters();

        $this->assertEquals(
            ['operator' => ['gte' => '*', 'lte' => '*']],
            $allowedFilters['due_at']
        );
    }

    public function test_allow_filter_with_a_single_operator_keeps_the_flat_shape()
    {
        $allowedFilters = $this->newRequestQueryObject()
            ->allowFilter(AllowedFilter::greaterOrEqualThan('due_at'))
            ->getAllowedFilters();

        $this->assertEquals(
            ['operator' => 'gte', 'values' => '*'],
            $allowedFilters['due_at']
        );
    }

    public function test_allow_filter_with_two_operators_and_different_value_restrictions_keeps_each_pattern()
    {
        $allowedFilters = $this->newRequestQueryObject()
            ->allowFilter(AllowedFilter::exact('priority', ['low', 'high']))
            ->allowFilter(AllowedFilter::similar('priority', ['medium*']))
            ->getAllowedFilters();

        $this->assertEquals(
            ['operator' => ['equal' => ['low', 'high'], 'like' => ['medium*']]],
            $allowedFilters['priority']
        );
    }

    // ---------------------------------------------------------------
    // userAllowedFilters() operator selection
    // ---------------------------------------------------------------

    public function test_user_allowed_filters_selects_each_operator_by_its_bracket_key()
    {
        $requestQueryObject = $this->newRequestQueryObject([
            'filter' => ['due_at' => ['gte' => '2024-01-01', 'lte' => '2024-01-31']],
        ])
            ->allowFilter(AllowedFilter::greaterOrEqualThan('due_at'))
            ->allowFilter(AllowedFilter::lowerOrEqualThan('due_at'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals(
            [['gte' => '2024-01-01'], ['lte' => '2024-01-31']],
            $userFilters['due_at']
        );
    }

    public function test_user_allowed_filters_with_plain_value_uses_the_first_registered_operator()
    {
        $requestQueryObject = $this->newRequestQueryObject(['filter' => ['due_at' => '2024-01-01']])
            ->allowFilter(AllowedFilter::greaterOrEqualThan('due_at'))
            ->allowFilter(AllowedFilter::lowerOrEqualThan('due_at'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals(['2024-01-01'], $userFilters['due_at']);
    }

    public function test_user_allowed_filters_drops_an_unregistered_operator_key()
    {
        $requestQueryObject = $this->newRequestQueryObject(['filter' => ['due_at' => ['lte' => '2024-01-01']]])
            ->allowFilter(AllowedFilter::greaterOrEqualThan('due_at'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertArrayNotHasKey('due_at', $userFilters);
    }

    // ---------------------------------------------------------------
    // Comma-separated multi-values
    // ---------------------------------------------------------------

    public function test_user_allowed_filters_drops_invalid_comma_values_and_keeps_valid_ones()
    {
        $requestQueryObject = $this->newRequestQueryObject(['filter' => ['status' => 'Active,Inactive']])
            ->allowFilter(AllowedFilter::exact('status', ['Active', 'Archived']));

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals(['Active'], $userFilters['status']);
    }

    public function test_user_allowed_filters_keeps_all_unrestricted_comma_values()
    {
        $requestQueryObject = $this->newRequestQueryObject(['filter' => ['status' => 'Active,Archived']])
            ->allowFilter(AllowedFilter::exact('status'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals(['Active,Archived'], $userFilters['status']);
    }

    // ---------------------------------------------------------------
    // Scoped filters
    // ---------------------------------------------------------------

    public function test_scoped_filter_truthy_value_still_works_with_default_pattern()
    {
        $requestQueryObject = $this->newRequestQueryObject(['filter' => ['active' => '1']])
            ->allowScopedFilter('active');

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals(['1'], $userFilters['active']);
    }

    public function test_scoped_filter_with_named_arguments_uses_default_registration()
    {
        $requestQueryObject = $this->newRequestQueryObject([
            'filter' => ['dueBetween' => ['from' => '2024-01-01', 'to' => '2024-01-31']],
        ])->allowScopedFilter('dueBetween');

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals(
            [['from' => '2024-01-01'], ['to' => '2024-01-31']],
            $userFilters['dueBetween']
        );
    }

    public function test_scoped_filter_with_explicit_pattern_still_enforces_it()
    {
        $requestQueryObject = $this->newRequestQueryObject([
            'filter' => ['status' => ['status1' => 'Active', 'status2' => 'Deleted']],
        ])->allowScopedFilter('status', ['Active', 'Archived']);

        $userFilters = $requestQueryObject->userAllowedFilters();

        // "Deleted" isn't part of the explicit pattern, so the whole scope call is dropped.
        $this->assertArrayNotHasKey('status', $userFilters);
    }

    // ---------------------------------------------------------------
    // validate_params: HttpException instead of a plain 500
    // ---------------------------------------------------------------

    public function test_disallowed_filter_is_silently_dropped_by_default()
    {
        $requestQueryObject = $this->newRequestQueryObject(['filter' => ['status' => 'Active']]);

        $this->assertSame([], $requestQueryObject->userAllowedFilters());
    }

    public function test_disallowed_filter_throws_http_exception_when_validate_params_is_enabled()
    {
        config(['apiable.requests.validate_params' => true]);

        $requestQueryObject = $this->newRequestQueryObject(['filter' => ['status' => 'Active']]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('"status" is not filterable or contains invalid values');

        $requestQueryObject->userAllowedFilters();
    }

    public function test_invalid_filter_value_throws_http_exception_with_400_status_when_validate_params_is_enabled()
    {
        config(['apiable.requests.validate_params' => true]);

        $requestQueryObject = $this->newRequestQueryObject(['filter' => ['status' => 'Deleted']])
            ->allowFilter(AllowedFilter::exact('status', ['Active', 'Archived']));

        try {
            $requestQueryObject->userAllowedFilters();
            $this->fail('Expected an HttpException to be thrown.');
        } catch (HttpException $exception) {
            $this->assertSame(400, $exception->getStatusCode());
        }
    }
}
