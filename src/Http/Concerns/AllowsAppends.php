<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Http\AllowedAppends;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
trait AllowsAppends
{
    /**
     * @var array<string, array<string>>
     */
    protected $allowedAppends = [];

    /**
     * Get user append attributes from request.
     *
     * @return array
     */
    public function appends()
    {
        $appends = $this->request->get('appends', []);

        foreach ($appends as $type => $attributes) {
            $appends[$type] = explode(',', $attributes);
        }

        return array_filter($appends);
    }

    /**
     * Allow the include of model accessors (attributes).
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedAppends|class-string<\Illuminate\Database\Eloquent\Model>|string  $type
     * @param  array  $attributes
     * @return $this
     */
    public function allowAppends($type, $attributes = null)
    {
        if ($type instanceof AllowedAppends) {
            $this->allowedAppends = array_merge($this->allowedAppends, $type->toArray());

            return $this;
        }

        if (class_exists($type) && is_subclass_of($type, Model::class)) {
            $type = Apiable::getResourceType($type);
        }

        $this->allowedAppends = array_merge($this->allowedAppends, [$type => (array) $attributes]);

        return $this;
    }

    public function userAllowedAppends()
    {
        return $this->validator($this->appends())
            ->givingRules($this->allowedAppends)
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
     * Get list of allowed appends per resource type.
     *
     * @return array<string, array<string>>
     */
    public function getAllowedAppends()
    {
        return $this->allowedAppends;
    }
}
