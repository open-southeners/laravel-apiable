<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultFilter;
use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultSort;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\DefaultSort;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Walks registered routes and builds the raw endpoint schema data consumed by the
 * `apiable:types` TypeScript exporter, keyed by JSON:API resource type slug.
 *
 * Uses the same route-grouping/`#[DocumentedResource]` gating as {@see Generator} (only
 * controllers annotated with `#[DocumentedResource]` produce a schema entry, matching the
 * docs generator's attribute-only reflection limitation), but reads the query-param
 * attributes directly rather than going through the documentation-oriented {@see QueryParam}
 * value object, which collapses each filter to a single operator string and loses the
 * operator list/restricted-values shape a generated TypeScript schema needs to keep.
 */
class EndpointSchemaGenerator
{
    /**
     * Canonical operator ordering used to keep generated `filters[attr].operators` stable
     * across regenerations, mirroring the wire-format keys from {@see AllowedFilter::operatorKey()}.
     */
    private const OPERATOR_ORDER = ['equal', 'like', 'gt', 'gte', 'lt', 'lte', 'scope'];

    public function __construct(
        private readonly Router $router,
    ) {
        //
    }

    /**
     * Build and return the endpoint schema map, keyed by resource type slug and sorted for
     * stable diffs across regenerations.
     *
     * @param  array<string>  $only  Only include routes matching these patterns.
     * @param  array<string>  $exclude  Exclude routes matching these patterns (merged with config).
     * @return array<string, array<string, mixed>>
     */
    public function generate(array $only = [], array $exclude = []): array
    {
        /** @var array<string> $configExcluded */
        $configExcluded = config('apiable.documentation.excluded_routes', []);
        $excludedPatterns = array_merge($configExcluded, $exclude);

        /** @var array<string, non-empty-list<array{route: Route, method: string}>> $routesByController */
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

        $schemas = [];

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

            $resourceSlug = $modelClass !== null
                ? Apiable::getResourceType($modelClass)
                : $this->slugFromControllerName($controllerClass);

            $listRoute = $this->resolveListRoute($controllerRoutes);

            try {
                $methodReflection = new ReflectionMethod($controllerClass, $listRoute['method']);
            } catch (ReflectionException) {
                $methodReflection = null;
            }

            $classAttrs = $this->collectAttributeInstances($classReflection);
            $methodAttrs = $methodReflection !== null ? $this->collectAttributeInstances($methodReflection) : [];
            $attrs = array_merge($classAttrs, $methodAttrs);

            $schemas[$resourceSlug] = $this->buildSchema($resourceSlug, '/'.$listRoute['route']->uri(), $attrs);
        }

        ksort($schemas);

        return $schemas;
    }

    /**
     * Build the EndpointSchema-shaped array for a single resource from its merged attribute
     * instances.
     *
     * @param  list<object>  $attrs
     * @return array<string, mixed>
     */
    private function buildSchema(string $resourceSlug, string $path, array $attrs): array
    {
        $schema = [
            'resource' => $resourceSlug,
            'path' => $path,
            'filters' => $this->buildFilters($this->filterInstancesOf($attrs, FilterQueryParam::class)),
            'sorts' => $this->buildSorts($this->filterInstancesOf($attrs, SortQueryParam::class)),
            'includes' => $this->buildIncludes($this->filterInstancesOf($attrs, IncludeQueryParam::class)),
            'fields' => $this->buildFields($this->filterInstancesOf($attrs, FieldsQueryParam::class)),
            'appends' => $this->buildAppends($this->filterInstancesOf($attrs, AppendsQueryParam::class)),
        ];

        $defaultSort = $this->buildDefaultSort($this->filterInstancesOf($attrs, ApplyDefaultSort::class));

        if ($defaultSort !== null) {
            $schema['defaultSort'] = $defaultSort;
        }

        $defaultFilters = $this->buildDefaultFilters($this->filterInstancesOf($attrs, ApplyDefaultFilter::class));

        if (! empty($defaultFilters)) {
            $schema['defaultFilters'] = $defaultFilters;
        }

        return $schema;
    }

    /**
     * Group and merge repeated `#[FilterQueryParam]` attributes into `attr => {operators,
     * values?}` entries, combining operators/restricted-values registered across several
     * attributes for the same filtered attribute.
     *
     * @param  FilterQueryParam[]  $filterAttrs
     * @return array<string, array{operators: list<string>, values?: list<string>}>
     */
    private function buildFilters(array $filterAttrs): array
    {
        /** @var array<string, array{operators: list<string>, values: list<string>, unrestricted: bool}> $acc */
        $acc = [];

        foreach ($filterAttrs as $attr) {
            $entry = $acc[$attr->attribute] ?? ['operators' => [], 'values' => [], 'unrestricted' => false];

            $entry['operators'] = array_values(array_unique(array_merge($entry['operators'], $this->operatorsFor($attr->type))));

            $restricted = $this->restrictedValues($attr->values);

            if ($restricted === null) {
                $entry['unrestricted'] = true;
            } else {
                $entry['values'] = array_values(array_unique(array_merge($entry['values'], $restricted)));
            }

            $acc[$attr->attribute] = $entry;
        }

        ksort($acc);

        $filters = [];

        foreach ($acc as $attribute => $entry) {
            $filters[$attribute] = ['operators' => $this->sortOperators($entry['operators'])];

            if (! $entry['unrestricted'] && $entry['values'] !== []) {
                $values = $entry['values'];
                sort($values);
                $filters[$attribute]['values'] = $values;
            }
        }

        return $filters;
    }

    /**
     * @return list<string>
     */
    private function buildSorts(array $sortAttrs): array
    {
        $sorts = array_values(array_unique(array_map(
            static fn (SortQueryParam $attr) => $attr->attribute,
            $sortAttrs
        )));

        sort($sorts);

        return $sorts;
    }

    /**
     * @return list<string>
     */
    private function buildIncludes(array $includeAttrs): array
    {
        $includes = [];

        foreach ($includeAttrs as $attr) {
            /** @var IncludeQueryParam $attr */
            $relationships = is_array($attr->relationships) ? $attr->relationships : [$attr->relationships];
            $includes = array_merge($includes, $relationships);
        }

        $includes = array_values(array_unique($includes));
        sort($includes);

        return $includes;
    }

    /**
     * @return array<string, list<string>>
     */
    private function buildFields(array $fieldsAttrs): array
    {
        $fields = [];

        foreach ($fieldsAttrs as $attr) {
            /** @var FieldsQueryParam $attr */
            $slug = $this->resolveResourceType($attr->type);
            $fields[$slug] = array_values(array_unique(array_merge($fields[$slug] ?? [], $attr->fields)));
        }

        return $this->sortedNestedMap($fields);
    }

    /**
     * @return array<string, list<string>>
     */
    private function buildAppends(array $appendsAttrs): array
    {
        $appends = [];

        foreach ($appendsAttrs as $attr) {
            /** @var AppendsQueryParam $attr */
            $slug = $this->resolveResourceType($attr->type);
            $appends[$slug] = array_values(array_unique(array_merge($appends[$slug] ?? [], $attr->attributes)));
        }

        return $this->sortedNestedMap($appends);
    }

    /**
     * @param  ApplyDefaultSort[]  $defaultSortAttrs
     */
    private function buildDefaultSort(array $defaultSortAttrs): ?string
    {
        if (empty($defaultSortAttrs)) {
            return null;
        }

        /** @var ApplyDefaultSort $first */
        $first = $defaultSortAttrs[0];

        return $first->direction === DefaultSort::DESCENDANT ? "-{$first->attribute}" : $first->attribute;
    }

    /**
     * @param  ApplyDefaultFilter[]  $defaultFilterAttrs
     * @return array<string, string>
     */
    private function buildDefaultFilters(array $defaultFilterAttrs): array
    {
        $defaults = [];

        foreach ($defaultFilterAttrs as $attr) {
            /** @var ApplyDefaultFilter $attr */
            $value = is_array($attr->values) ? implode(',', array_map('strval', $attr->values)) : (string) $attr->values;

            if ($value === '' || $value === '*') {
                continue;
            }

            $defaults[$attr->attribute] = $value;
        }

        ksort($defaults);

        return $defaults;
    }

    /**
     * Map an `AllowedFilter` operator constant (or list of constants) from `#[FilterQueryParam]`
     * to its wire-format key(s) via {@see AllowedFilter::operatorKey()}.
     *
     * @param  int|array<int>|null  $type
     * @return list<string>
     */
    private function operatorsFor(int|array|null $type): array
    {
        $operators = is_array($type) ? $type : [$type ?? AllowedFilter::SIMILAR];

        return array_values(array_unique(array_map(
            static fn (int $operator) => AllowedFilter::operatorKey($operator),
            $operators
        )));
    }

    /**
     * @return list<string>
     */
    private function sortOperators(array $operators): array
    {
        usort($operators, static fn (string $a, string $b) => array_search($a, self::OPERATOR_ORDER, true) <=> array_search($b, self::OPERATOR_ORDER, true));

        return $operators;
    }

    /**
     * Normalise a `#[FilterQueryParam]`/`#[FieldsQueryParam]`-style `values` argument into a
     * restricted value list, or null when unrestricted (the `'*'` default).
     *
     * @return list<string>|null
     */
    private function restrictedValues(mixed $values): ?array
    {
        if ($values === '*') {
            return null;
        }

        if (is_array($values)) {
            return array_values(array_map('strval', $values));
        }

        if (is_string($values)) {
            $parts = array_values(array_filter(
                array_map('trim', explode(',', $values)),
                static fn (string $value) => $value !== ''
            ));

            return $parts !== [] ? $parts : null;
        }

        return null;
    }

    /**
     * @param  array<string, list<string>>  $map
     * @return array<string, list<string>>
     */
    private function sortedNestedMap(array $map): array
    {
        foreach ($map as &$values) {
            sort($values);
        }

        unset($values);

        ksort($map);

        return $map;
    }

    /**
     * Resolve a documented resource type down to the runtime JSON:API type slug, matching
     * {@see QueryParam::resolveResourceType()}.
     */
    private function resolveResourceType(string $type): string
    {
        if (class_exists($type) && is_subclass_of($type, Model::class)) {
            return Apiable::getResourceType($type);
        }

        return $type;
    }

    /**
     * Fallback resource slug for a controller with no `#[EndpointResource]` attribute: strip
     * a trailing "Controller" suffix from the class basename and snake_case it.
     */
    private function slugFromControllerName(string $controllerClass): string
    {
        $basename = class_basename($controllerClass);
        $basename = str_ends_with($basename, 'Controller') ? substr($basename, 0, -strlen('Controller')) : $basename;

        return Str::snake($basename);
    }

    /**
     * Pick the route that best represents the resource's "list"/collection endpoint (the one
     * filters/sorts/includes/fields/appends attributes are declared against), preferring an
     * `index` action, then a route with no bound parameters, then the route with the fewest.
     *
     * @param  non-empty-list<array{route: Route, method: string}>  $controllerRoutes
     * @return array{route: Route, method: string}
     */
    private function resolveListRoute(array $controllerRoutes): array
    {
        $indexEntry = null;
        $noParamEntry = null;
        $fewestParamsEntry = null;

        foreach ($controllerRoutes as $entry) {
            $paramCount = substr_count($entry['route']->uri(), '{');

            if ($entry['method'] === 'index' && $indexEntry === null) {
                $indexEntry = $entry;
            }

            if ($paramCount === 0 && $noParamEntry === null) {
                $noParamEntry = $entry;
            }

            if ($fewestParamsEntry === null || $paramCount < substr_count($fewestParamsEntry['route']->uri(), '{')) {
                $fewestParamsEntry = $entry;
            }
        }

        return $indexEntry ?? $noParamEntry ?? $fewestParamsEntry;
    }

    /**
     * Collect every repeatable query-param/default attribute instance from a reflected class
     * or method (unlike {@see Generator::collectQueryParams()}, this keeps the raw attribute
     * instances instead of converting them to documentation {@see QueryParam} value objects).
     *
     * @param  ReflectionClass<object>|ReflectionMethod  $reflected
     * @return list<object>
     */
    private function collectAttributeInstances(ReflectionClass|ReflectionMethod $reflected): array
    {
        $attributeClasses = [
            FilterQueryParam::class,
            SortQueryParam::class,
            IncludeQueryParam::class,
            FieldsQueryParam::class,
            AppendsQueryParam::class,
            ApplyDefaultSort::class,
            ApplyDefaultFilter::class,
        ];

        $attrs = array_filter(
            $reflected->getAttributes(),
            static fn (ReflectionAttribute $a) => in_array($a->getName(), $attributeClasses, true)
        );

        return array_values(array_map(static fn (ReflectionAttribute $a) => $a->newInstance(), $attrs));
    }

    /**
     * @param  list<object>  $attrs
     * @param  class-string  $class
     * @return list<object>
     */
    private function filterInstancesOf(array $attrs, string $class): array
    {
        return array_values(array_filter($attrs, static fn (object $attr) => $attr instanceof $class));
    }

    /**
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
