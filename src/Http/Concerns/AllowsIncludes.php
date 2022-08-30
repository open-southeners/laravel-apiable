<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

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
