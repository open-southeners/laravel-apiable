<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\Request as HttpRequest;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class RequestTest extends TestCase
{
    public function testRequestWantsJsonApi()
    {
        Route::get('/', function (Request $request) {
            return $request->wantsJsonApi() ? 'foo' : 'bar';
        });

        $this->get('/', ['Accept' => HttpRequest::JSON_API_HEADER])->assertSee('foo');

        $this->get('/', ['Content-Type' => HttpRequest::JSON_API_HEADER])->assertSee('foo');

        $this->get('/', ['Accept' => 'application/json'])->assertSee('bar');

        $this->get('/', ['Content-Type' => 'application/json'])->assertSee('bar');
    }
}
