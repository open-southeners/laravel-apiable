<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\ApplyFiltersToQuery;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class ApplyFiltersToQueryTest extends TestCase
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

    protected function filteredTitles(string $rawQueryString, AllowedFilter $filter): array
    {
        $requestQueryObject = $this->newRequestQueryObject($rawQueryString)->allowFilter($filter);

        $query = (new ApplyFiltersToQuery)->from(
            $requestQueryObject,
            fn (RequestQueryObject $requestQueryObject) => $requestQueryObject->query
        );

        return $query->orderBy('title')->pluck('title')->all();
    }

    public function test_not_equal_excludes_every_value_in_a_comma_list()
    {
        Post::create(['title' => 'draft post', 'status' => 'draft']);
        Post::create(['title' => 'archived post', 'status' => 'archived']);
        Post::create(['title' => 'published post', 'status' => 'published']);

        $this->assertSame(
            ['published post'],
            $this->filteredTitles('filter[status][not_equal]=draft,archived', AllowedFilter::notEqual('status'))
        );

        // Positive lists retain their existing OR semantics.
        $this->assertSame(
            ['archived post', 'draft post'],
            $this->filteredTitles('filter[status][equal]=draft,archived', AllowedFilter::exact('status'))
        );
    }

    public function test_not_like_excludes_every_substring_in_a_comma_list()
    {
        Post::create(['title' => 'draft release', 'status' => 'active']);
        Post::create(['title' => 'archived release', 'status' => 'active']);
        Post::create(['title' => 'current release', 'status' => 'active']);

        $this->assertSame(
            ['current release'],
            $this->filteredTitles('filter[title][not_like]=draft,archived', AllowedFilter::notLike('title'))
        );
    }

    public function test_not_equal_uses_and_for_values_on_a_related_attribute()
    {
        $draft = User::create(['name' => 'draft author', 'email' => 'draft@example.com', 'password' => 'secret']);
        $published = User::create(['name' => 'published author', 'email' => 'published@example.com', 'password' => 'secret']);
        Post::create(['title' => 'draft post', 'status' => 'active', 'author_id' => $draft->id]);
        Post::create(['title' => 'published post', 'status' => 'active', 'author_id' => $published->id]);

        $this->assertSame(
            ['published post'],
            $this->filteredTitles('filter[author.name][not_equal]=draft%20author,archived%20author', AllowedFilter::notEqual('author.name'))
        );
    }

    public function test_not_equal_keeps_literal_commas_with_strict_comma_encoding()
    {
        config(['apiable.requests.strict_comma_encoding' => true]);

        Post::create(['title' => 'comma title', 'status' => 'draft,archived']);
        Post::create(['title' => 'draft title', 'status' => 'draft']);
        Post::create(['title' => 'other title', 'status' => 'published']);

        $this->assertSame(
            ['draft title', 'other title'],
            $this->filteredTitles('filter[status][not_equal]=draft%2Carchived', AllowedFilter::notEqual('status'))
        );
    }

    public function test_a_whitespace_only_value_is_not_silently_dropped()
    {
        $requestQueryObject = $this->newRequestQueryObject('filter[title]=x,%20,y')
            ->allowFilter(AllowedFilter::exact('title'));

        $this->assertEquals(['x', ' ', 'y'], $this->appliedFilterBindings($requestQueryObject));
    }

    public function test_a_whitespace_only_value_is_not_dropped_with_strict_comma_encoding_on()
    {
        config(['apiable.requests.strict_comma_encoding' => true]);

        $requestQueryObject = $this->newRequestQueryObject('filter[title]=x,%20,y')
            ->allowFilter(AllowedFilter::exact('title'));

        $this->assertEquals(['x', ' ', 'y'], $this->appliedFilterBindings($requestQueryObject));
    }

    public function test_a_truly_empty_value_from_a_repeated_comma_is_still_dropped()
    {
        $requestQueryObject = $this->newRequestQueryObject('filter[title]=x,,y')
            ->allowFilter(AllowedFilter::exact('title'));

        $this->assertEquals(['x', 'y'], $this->appliedFilterBindings($requestQueryObject));
    }
}
