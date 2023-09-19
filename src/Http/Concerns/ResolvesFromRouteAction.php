<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultFilter;
use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultSort;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\QueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchFilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\JsonApiResponse
 */
trait ResolvesFromRouteAction
{
    /**
     * Resolves allowed query parameters from current route if possible.
     *
     * @return void
     */
    protected function resolveFromRoute()
    {
        $routeAction = Route::currentRouteAction();

        if (! $routeAction) {
            return;
        }

        $routeAction = explode('@', $routeAction);

        if ($controller = array_shift($routeAction)) {
            $this->resolveAttributesFrom(new ReflectionClass($controller));

            if ($method = array_shift($routeAction)) {
                $this->resolveAttributesFrom(new ReflectionMethod($controller, $method));
            }
        }
    }

    /**
     * Get PHP query param attributes from reflected class or method.
     *
     * @param  \ReflectionClass|\ReflectionMethod  $reflected
     * @return void
     */
    protected function resolveAttributesFrom($reflected)
    {
        $allowedQueryParams = array_filter($reflected->getAttributes(), function (ReflectionAttribute $attribute) {
            return is_subclass_of($attribute->getName(), QueryParam::class)
                || $attribute->getName() === ForceAppendAttribute::class
                || in_array($attribute->getName(), [ApplyDefaultFilter::class, ApplyDefaultSort::class]);
        });

        foreach ($allowedQueryParams as $allowedQueryParam) {
            $attributeInstance = $allowedQueryParam->newInstance();

            match (true) {
                $attributeInstance instanceof SearchQueryParam => $this->allowSearch($attributeInstance->allowSearch),
                $attributeInstance instanceof SearchFilterQueryParam => $this->allowSearchFilter($attributeInstance->attribute, $attributeInstance->values),
                $attributeInstance instanceof FilterQueryParam => $this->allowFilter($attributeInstance->attribute, $attributeInstance->type, $attributeInstance->values),
                $attributeInstance instanceof SortQueryParam => $this->allowSort($attributeInstance->attribute, $attributeInstance->direction),
                $attributeInstance instanceof IncludeQueryParam => $this->allowInclude($attributeInstance->relationships),
                $attributeInstance instanceof FieldsQueryParam => $this->allowFields($attributeInstance->type, $attributeInstance->fields),
                $attributeInstance instanceof AppendsQueryParam => $this->allowAppends($attributeInstance->type, $attributeInstance->attributes),
                $attributeInstance instanceof ApplyDefaultSort => $this->applyDefaultSort($attributeInstance->attribute, $attributeInstance->direction),
                $attributeInstance instanceof ApplyDefaultFilter => $this->applyDefaultFilter($attributeInstance->attribute, $attributeInstance->operator, $attributeInstance->values),
                default => null,
            };
        }
    }
}
