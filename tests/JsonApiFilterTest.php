<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\ApplyFiltersToQuery;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;

class JsonApiFilterTest extends TestCase
{
    public function testJsonApiFiltersGetsTheResultFiltered()
    {
        Route::get('/', function (Request $request) {
            $requestQueryObjectInstance = new RequestQueryObject($request, Post::query());

            $query = app(Pipeline::class)->send($requestQueryObjectInstance)->via('from')->through([
                ApplyFiltersToQuery::class,
            ])->thenReturn()->get();

            return response()->json($query->get());
        });

        $response = $this->getJson('/');

        var_dump($response->json());
    }
}
