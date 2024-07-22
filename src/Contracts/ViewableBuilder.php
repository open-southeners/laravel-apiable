<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 */
interface ViewableBuilder
{
    /**
     * Scope applied to the query for show/hide items.
     *
     * @return \Illuminate\Database\Eloquent\Builder<T>
     */
    public function viewable(?Authenticatable $user = null);
}
