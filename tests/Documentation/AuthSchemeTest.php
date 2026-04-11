<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation;

use OpenSoutheners\LaravelApiable\Documentation\AuthScheme;
use PHPUnit\Framework\TestCase;

class AuthSchemeTest extends TestCase
{
    private array $middlewareMap = [
        'auth:sanctum' => 'bearer',
        'auth:api'     => 'bearer',
        'auth.basic'   => 'basic',
    ];

    public function test_resolves_bearer_from_sanctum_middleware(): void
    {
        $scheme = AuthScheme::fromRouteMiddleware(['auth:sanctum'], $this->middlewareMap);

        $this->assertNotNull($scheme);
        $this->assertSame('bearer', $scheme->type);
        $this->assertSame('auth:sanctum', $scheme->middleware);
    }

    public function test_resolves_bearer_from_api_middleware(): void
    {
        $scheme = AuthScheme::fromRouteMiddleware(['auth:api'], $this->middlewareMap);

        $this->assertNotNull($scheme);
        $this->assertSame('bearer', $scheme->type);
    }

    public function test_resolves_basic_from_auth_basic_middleware(): void
    {
        $scheme = AuthScheme::fromRouteMiddleware(['auth.basic'], $this->middlewareMap);

        $this->assertNotNull($scheme);
        $this->assertSame('basic', $scheme->type);
    }

    public function test_unknown_middleware_returns_null(): void
    {
        $scheme = AuthScheme::fromRouteMiddleware(['throttle:60,1', 'verified'], $this->middlewareMap);

        $this->assertNull($scheme);
    }

    public function test_empty_middleware_returns_null(): void
    {
        $this->assertNull(AuthScheme::fromRouteMiddleware([], $this->middlewareMap));
    }

    public function test_to_array(): void
    {
        $scheme = new AuthScheme('bearer', 'auth:sanctum');

        $this->assertSame(['type' => 'bearer', 'middleware' => 'auth:sanctum'], $scheme->toArray());
    }
}
