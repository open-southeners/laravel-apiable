<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use OpenSoutheners\LaravelApiable\Http\AllowedFields;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
trait AllowsFields
{
    /**
     * @var array<string, array<string>>
     */
    protected $allowedFields = [];

    /**
     * Get all fields from request.
     *
     * @return array
     */
    public function fields()
    {
        $fields = $this->request->get('fields', []);

        foreach ($fields as $type => $columns) {
            $fields[$type] = explode(',', $columns);
        }

        return array_filter($fields);
    }

    /**
     * Allow sparse fields (columns or accessors) for a specific resource type.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedFields|string  $type
     * @param  array<string>|string|null  $attributes
     * @return $this
     */
    public function allowFields($type, $attributes = null)
    {
        if ($type instanceof AllowedFields) {
            $this->allowedFields = array_merge($this->allowedFields, $type->toArray());
        } else {
            $this->allowedFields = array_merge($this->allowedFields, [$type => [$attributes]]);
        }

        return $this;
    }

    /**
     * Get list of allowed fields per resource type.
     *
     * @return array<string, array<string>>
     */
    public function getAllowedFields()
    {
        return $this->allowedFields;
    }
}
