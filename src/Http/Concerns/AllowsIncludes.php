<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;
use OpenSoutheners\LaravelApiable\Http\RequestQueryObject;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @mixin RequestQueryObject
 */
trait AllowsIncludes
{
    /**
     * @var array<string>
     */
    protected $allowedIncludes = [];

    /**
     * Get user includes relationships from request.
     *
     * @return array
     */
    public function includes()
    {
        return $this->flexUrl()->getIncludes();
    }

    /**
     * Allow include relationship to the response.
     *
     * @param  AllowedInclude|array|string  $relationship
     * @return $this
     */
    public function allowInclude($relationship)
    {
        $relationshipAsArray = $relationship instanceof Arrayable
            ? $relationship->toArray()
            : (array) $relationship;

        $this->allowedIncludes = array_merge($this->allowedIncludes, $relationshipAsArray);

        return $this;
    }

    public function userAllowedIncludes()
    {
        $maxIncludeDepth = (int) (Apiable::config('responses.max_include_depth') ?? 3);

        return $this->validator($this->includes())
            ->givingRules(false)
            ->when(
                fn ($key, $modifiers, $values, $rules) => in_array($values, $this->allowedIncludes)
                    && $this->includeDepth($values) <= $maxIncludeDepth,
                fn ($key, $values) => throw new HttpException(400, in_array($values, $this->allowedIncludes)
                    ? sprintf('"%s" exceeds maximum include depth of %d', $values, $maxIncludeDepth)
                    : sprintf('"%s" cannot be included', $values))
            )
            ->validate();
    }

    /**
     * Get the nesting depth of the given include path (dots separate levels).
     *
     * @param  array|string  $value
     */
    protected function includeDepth($value): int
    {
        $value = is_array($value) ? implode('.', $value) : $value;

        return substr_count((string) $value, '.') + 1;
    }

    /**
     * Get list of allowed includes.
     *
     * @return array<string>
     */
    public function getAllowedIncludes()
    {
        return $this->allowedIncludes;
    }
}
