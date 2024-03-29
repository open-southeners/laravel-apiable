<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Http\AllowedFields;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

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
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedFields|class-string<\Illuminate\Database\Eloquent\Model>|string  $type
     * @param  array<string>|string|null  $attributes
     * @return $this
     */
    public function allowFields($type, $attributes = null)
    {
        if ($type instanceof AllowedFields) {
            $this->allowedFields = array_merge($this->allowedFields, $type->toArray());

            return $this;
        }

        if (class_exists($type) && is_subclass_of($type, Model::class)) {
            $type = Apiable::getResourceType($type);
        }

        $this->allowedFields = array_merge($this->allowedFields, [$type => (array) $attributes]);

        return $this;
    }

    public function userAllowedFields()
    {
        return $this->validator($this->fields())
            ->givingRules($this->allowedFields)
            ->when(
                function ($key, $modifiers, $values, $rules, &$valids) {
                    $valids = array_intersect($values, $rules);

                    return empty(array_diff($values, $rules));
                },
                fn ($key, $values) => throw new Exception(sprintf('"%s" fields for resource type "%s" cannot be sparsed', implode(', ', $values), $key))
            )
            ->validate();
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
