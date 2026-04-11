<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

use Illuminate\Routing\Router;
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\QueryParam as QueryParamAttribute;
use OpenSoutheners\LaravelApiable\Attributes\SearchFilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedEndpointSection;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Iterates registered routes and builds a Resource[] documentation tree.
 */
class Generator
{
    public function __construct(
        private readonly Router $router,
    ) {
        //
    }

    /**
     * Build and return the full documentation tree.
     *
     * @param  array<string>  $only  Only include routes matching these patterns.
     * @param  array<string>  $exclude  Exclude routes matching these patterns (merged with config).
     * @return OpenSouthenersLaravelApiableDocumentationResource[]
     */
    public function generate(array $only = [], array $exclude = []): array
    {
        /** @var array<string> $configExcluded */
        $configExcluded = config('apiable.documentation.excluded_routes', []);
        /** @var array<string, string> $middlewareMap */
        $middlewareMap = config('apiable.documentation.auth.middleware_map', []);
        /** @var bool $detectMiddleware */
        $detectMiddleware = (bool) config('apiable.documentation.auth.detect_middleware', true);

        $excludedPatterns = array_merge($configExcluded, $exclude);

        /** @var array<string, list<array{route: \Illuminate\Routing\Route, method: string}>> $routesByController */
        $routesByController = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();

            if (! empty($only) && ! $this->matchesAny($uri, $only)) {
                continue;
            }

            if ($this->matchesAny($uri, $excludedPatterns)) {
                continue;
            }

            $action = $route->getAction();

            if (! isset($action['controller']) || ! is_string($action['controller'])) {
                continue;
            }

            [$controllerClass, $methodName] = array_pad(explode('@', $action['controller'], 2), 2, '__invoke');

            $routesByController[$controllerClass][] = [
                'route' => $route,
                'method' => $methodName,
            ];
        }

        $resources = [];

        foreach ($routesByController as $controllerClass => $controllerRoutes) {
            try {
                $classReflection = new ReflectionClass($controllerClass);
            } catch (ReflectionException) {
                continue;
            }

            $resourceAttrs = $classReflection->getAttributes(DocumentedResource::class);

            if (empty($resourceAttrs)) {
                continue;
            }

            /** @var DocumentedResource $docResource */
            $docResource = $resourceAttrs[0]->newInstance();

            $endpointResourceAttrs = $classReflection->getAttributes(EndpointResource::class);
            $modelClass = ! empty($endpointResourceAttrs)
                ? $endpointResourceAttrs[0]->newInstance()->resource
                : null;

            // Collect class-level query params (shared across all endpoints in this resource)
            $classQueryParams = $this->collectQueryParams($classReflection);

            $endpoints = [];

            foreach ($controllerRoutes as $routeData) {
                $route = $routeData['route'];
                $methodName = $routeData['method'];

                try {
                    $methodReflection = new ReflectionMethod($controllerClass, $methodName);
                } catch (ReflectionException) {
                    continue;
                }

                $auth = $detectMiddleware
                    ? AuthScheme::fromRouteMiddleware($route->gatherMiddleware(), $middlewareMap)
                    : null;

                $methodQueryParams = $this->collectQueryParams($methodReflection);
                $queryParams = array_merge($classQueryParams, $methodQueryParams);

                $sectionAttrs = $methodReflection->getAttributes(DocumentedEndpointSection::class);
                $docSection = ! empty($sectionAttrs) ? $sectionAttrs[0]->newInstance() : null;

                $title = ($docSection !== null && $docSection->title !== '')
                    ? $docSection->title
                    : ucwords(str_replace(['-', '_', '.'], ' ', $methodName));

                $description = ($docSection !== null && $docSection->description !== '')
                    ? $docSection->description
                    : DocblockExtractor::fromReflection($methodReflection);

                foreach ($route->methods() as $httpMethod) {
                    if ($httpMethod === 'HEAD') {
                        continue;
                    }

                    $endpoints[] = new Endpoint(
                        uri: $route->uri(),
                        method: $httpMethod,
                        title: $title,
                        description: $description,
                        queryParams: $queryParams,
                        auth: $auth,
                    );
                }
            }

            $resources[] = new Resource(
                name: $docResource->name,
                description: $docResource->description,
                endpoints: $endpoints,
                modelClass: $modelClass,
            );
        }

        return $resources;
    }

    /**
     * Collect QueryParam value objects from a reflected class or method.
     *
     * @param  ReflectionClass<object>|ReflectionMethod  $reflected
     * @return QueryParam[]
     */
    private function collectQueryParams(ReflectionClass|ReflectionMethod $reflected): array
    {
        $queryParams = [];

        $attrs = array_filter(
            $reflected->getAttributes(),
            static fn (ReflectionAttribute $a) => is_subclass_of($a->getName(), QueryParamAttribute::class)
        );

        foreach ($attrs as $attr) {
            $instance = $attr->newInstance();

            $queryParams[] = match (true) {
                $instance instanceof FilterQueryParam => QueryParam::fromFilterAttribute($instance),
                $instance instanceof SortQueryParam => QueryParam::fromSortAttribute($instance),
                $instance instanceof IncludeQueryParam => QueryParam::fromIncludeAttribute($instance),
                $instance instanceof FieldsQueryParam => QueryParam::fromFieldsAttribute($instance),
                $instance instanceof AppendsQueryParam => QueryParam::fromAppendsAttribute($instance),
                $instance instanceof SearchQueryParam => QueryParam::fromSearchAttribute($instance),
                $instance instanceof SearchFilterQueryParam => QueryParam::fromSearchFilterAttribute($instance),
                default => null,
            };
        }

        return array_values(array_filter($queryParams));
    }

    /**
     * Check whether a URI matches any of the given glob patterns.
     *
     * @param  array<string>  $patterns
     */
    private function matchesAny(string $uri, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }
}
