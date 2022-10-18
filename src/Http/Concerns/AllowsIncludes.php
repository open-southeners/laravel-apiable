<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
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
        return array_filter(explode(',', $this->request->get('include', '')));
    }

    /**
     * Allow include relationship to the response.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedInclude|array|string  $relationship
     * @return $this
     */
    public function allowInclude($relationship)
    {
        $this->allowedIncludes = array_merge($this->allowedIncludes, (array) $relationship);

        return $this;
    }

    public function userAllowedIncludes()
    {
        return $this->validator($this->includes())
            ->givingRules(false)
            ->when(
                fn ($key, $modifiers, $values, $rules) => in_array($values, $this->allowedIncludes),
                fn ($key, $values) => throw new Exception(sprintf('"%s" cannot be included', $values))
            )
            ->validate();
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
