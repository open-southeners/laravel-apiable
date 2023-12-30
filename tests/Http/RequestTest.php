<?php

namespace OpenSoutheners\LaravelApiable\Tests\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Tests\TestCase;

class RequestTest extends TestCase
{
    public function testRequestWantsJsonApi()
    {
        Route::get('/', function (Request $request) {
            return $request->wantsJsonApi() ? 'foo' : 'bar';
        });

        $this->get('/', ['Accept' => 'application/vnd.api+json'])->assertSee('foo');

        $this->get('/', ['Accept' => 'application/json'])->assertSee('bar');
    }
}
