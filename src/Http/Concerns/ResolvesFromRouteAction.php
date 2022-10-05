<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\QueryParam;
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

        [$controller, $method] = explode('@', $routeAction);

        $this->resolveAttributesFrom(new ReflectionClass($controller));

        $this->resolveAttributesFrom(new ReflectionMethod($controller, $method));
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
            return is_subclass_of($attribute->getName(), QueryParam::class);
        });

        foreach ($allowedQueryParams as $allowedQueryParam) {
            $attributeInstance = $allowedQueryParam->newInstance();

            match (true) {
                $attributeInstance instanceof SearchQueryParam => $this->allowSearch($attributeInstance->allowSearch),
                $attributeInstance instanceof FilterQueryParam => $this->allowFilter($attributeInstance->attribute, $attributeInstance->type, $attributeInstance->values),
                $attributeInstance instanceof SortQueryParam => $this->allowSort($attributeInstance->attribute, $attributeInstance->direction),
                $attributeInstance instanceof IncludeQueryParam => $this->allowInclude($attributeInstance->relationships),
                $attributeInstance instanceof FieldsQueryParam => $this->allowFields($attributeInstance->type, $attributeInstance->fields),
                $attributeInstance instanceof AppendsQueryParam => $this->allowAppends($attributeInstance->type, $attributeInstance->attributes),
            };
        }
    }
}
