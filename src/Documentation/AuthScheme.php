<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

/**
 * Value object representing a detected route authentication scheme.
 */
class AuthScheme
{
    public function __construct(
        public readonly string $type,       // 'bearer' | 'basic'
        public readonly string $middleware,
    ) {
        //
    }

    /**
     * Resolve an AuthScheme from a route's middleware list using the config map.
     *
     * @param  array<string>  $middleware
     * @param  array<string, string>  $map  middleware-name => scheme-type
     */
    public static function fromRouteMiddleware(array $middleware, array $map): ?self
    {
        foreach ($middleware as $m) {
            // Normalize: strip parameters (e.g. "auth:sanctum" → key checked as-is first)
            if (isset($map[$m])) {
                return new self($map[$m], $m);
            }

            // Also try without parameters (e.g. "throttle:60,1" → "throttle")
            $base = explode(':', $m, 2)[0];
            if ($base !== $m && isset($map[$base])) {
                return new self($map[$base], $m);
            }
        }

        return null;
    }

    /**
     * Return the scheme as an array for use in templates and exporters.
     *
     * @return array{type: string, middleware: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'middleware' => $this->middleware,
        ];
    }
}
