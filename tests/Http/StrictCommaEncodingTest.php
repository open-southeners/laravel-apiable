<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\ApplyFiltersToQuery;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

/**
 * Phase 2 of the flex-url migration: apiable's request-query parsing (filter/sort/include/
 * fields/appends) is now backed entirely by `open-southeners/flex-url`, fed the raw
 * `QUERY_STRING` rather than `Request::fullUrl()` (which Symfony would otherwise normalise,
 * re-encoding every comma uniformly and silently defeating `strict_comma_encoding`).
 *
 * These tests cover the newly opt-in `apiable.requests.strict_comma_encoding` config key: off
 * (the default) must behave byte-identically to the pre-migration hand-rolled parsers; on, a
 * literal comma inside one filter value (sent as `%2C`) must survive end to end into the applied
 * query, rather than being silently re-split by the two redundant `explode(',', ...)` call sites
 * this migration removes (`AllowsFilters::operatorFilterValuesMatchRules()` and
 * `ApplyFiltersToQuery::wrapIfRelatedQuery()`).
 */
class StrictCommaEncodingTest extends TestCase
{
    protected function newRequestQueryObject(string $rawQueryString): RequestQueryObject
    {
        $request = Request::create('/?'.$rawQueryString, 'GET');

        return (new RequestQueryObject($request))->setQuery(Post::query());
    }

    protected function appliedFilterBindings(RequestQueryObject $requestQueryObject): array
    {
        $query = (new ApplyFiltersToQuery)->from(
            $requestQueryObject,
            fn (RequestQueryObject $requestQueryObject) => $requestQueryObject->query
        );

        return $query->getBindings();
    }

    // ---------------------------------------------------------------
    // strict_comma_encoding off (default): byte-identical regression gate
    // ---------------------------------------------------------------

    public function test_strict_comma_encoding_off_still_splits_a_plain_comma_separated_filter_value()
    {
        $requestQueryObject = $this->newRequestQueryObject('filter[title]=foo,bar')
            ->allowFilter(AllowedFilter::exact('title'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals(['foo,bar'], $userFilters['title']);
        $this->assertEquals(['foo', 'bar'], $this->appliedFilterBindings($requestQueryObject));
    }

    public function test_strict_comma_encoding_off_splits_an_escaped_comma_the_same_as_a_raw_one()
    {
        config(['apiable.requests.strict_comma_encoding' => false]);

        $requestQueryObject = $this->newRequestQueryObject('filter[title]=foo%2Cbar,baz')
            ->allowFilter(AllowedFilter::exact('title'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        // Off means every comma (raw or %2C) is a separator: three values, none containing a comma.
        $this->assertEquals(['foo,bar,baz'], $userFilters['title']);
        $this->assertEquals(['foo', 'bar', 'baz'], $this->appliedFilterBindings($requestQueryObject));
    }

    // ---------------------------------------------------------------
    // strict_comma_encoding on: a literal comma survives
    // ---------------------------------------------------------------

    public function test_strict_comma_encoding_on_preserves_a_literal_comma_inside_a_single_filter_value()
    {
        config(['apiable.requests.strict_comma_encoding' => true]);

        $requestQueryObject = $this->newRequestQueryObject('filter[title]=foo%2Cbar')
            ->allowFilter(AllowedFilter::exact('title'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        // With strict encoding on, a literal-comma value is kept as a real array rather than
        // being rejoined into a comma string — this is exactly what makes it safe from the
        // (now-removed) re-split at ApplyFiltersToQuery::wrapIfRelatedQuery().
        $this->assertEquals([['foo,bar']], $userFilters['title']);
        $this->assertEquals(['foo,bar'], $this->appliedFilterBindings($requestQueryObject));
    }

    public function test_strict_comma_encoding_on_still_splits_a_raw_separator_comma()
    {
        config(['apiable.requests.strict_comma_encoding' => true]);

        $requestQueryObject = $this->newRequestQueryObject('filter[title]=foo,bar')
            ->allowFilter(AllowedFilter::exact('title'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals([['foo', 'bar']], $userFilters['title']);
        $this->assertEquals(['foo', 'bar'], $this->appliedFilterBindings($requestQueryObject));
    }

    public function test_strict_comma_encoding_on_distinguishes_a_literal_comma_from_a_real_separator_in_the_same_value()
    {
        config(['apiable.requests.strict_comma_encoding' => true]);

        $requestQueryObject = $this->newRequestQueryObject('filter[title]=foo%2Cbar,baz')
            ->allowFilter(AllowedFilter::exact('title'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals([['foo,bar', 'baz']], $userFilters['title']);

        $bindings = $this->appliedFilterBindings($requestQueryObject);

        $this->assertCount(2, $bindings);
        $this->assertContains('foo,bar', $bindings);
        $this->assertContains('baz', $bindings);
    }

    public function test_strict_comma_encoding_on_still_selects_each_operator_by_its_bracket_key()
    {
        config(['apiable.requests.strict_comma_encoding' => true]);

        $requestQueryObject = $this->newRequestQueryObject('filter[due_at][gte]=2024-01-01&filter[due_at][lte]=2024-01-31')
            ->allowFilter(AllowedFilter::greaterOrEqualThan('due_at'))
            ->allowFilter(AllowedFilter::lowerOrEqualThan('due_at'));

        $userFilters = $requestQueryObject->userAllowedFilters();

        $this->assertEquals(
            [['gte' => ['2024-01-01']], ['lte' => ['2024-01-31']]],
            $userFilters['due_at']
        );
    }

    // ---------------------------------------------------------------
    // strict_comma_encoding on: other readers keep working
    // ---------------------------------------------------------------

    public function test_strict_comma_encoding_on_includes_still_reads_correctly()
    {
        config(['apiable.requests.strict_comma_encoding' => true]);

        $requestQueryObject = $this->newRequestQueryObject('include=author,tags')
            ->allowInclude('author')
            ->allowInclude('tags');

        $this->assertEquals(['author', 'tags'], array_values($requestQueryObject->userAllowedIncludes()));
    }

    public function test_strict_comma_encoding_on_sorts_still_reads_correctly()
    {
        config(['apiable.requests.strict_comma_encoding' => true]);

        $requestQueryObject = $this->newRequestQueryObject('sort=-created_at')
            ->allowSort('created_at');

        $this->assertEquals(['created_at' => 'desc'], $requestQueryObject->userAllowedSorts());
    }

    public function test_strict_comma_encoding_on_fields_still_read_correctly()
    {
        config(['apiable.requests.strict_comma_encoding' => true]);

        $requestQueryObject = $this->newRequestQueryObject('fields[post]=title,content')
            ->allowFields('post', ['title', 'content']);

        $this->assertEquals(['post' => ['title', 'content']], $requestQueryObject->userAllowedFields());
    }
}
