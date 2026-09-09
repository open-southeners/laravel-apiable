<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\ApplyFiltersToQuery;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
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
