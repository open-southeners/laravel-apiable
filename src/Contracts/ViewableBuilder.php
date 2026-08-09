<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 */
interface ViewableBuilder
{
    /**
     * Scope applied to the query for show/hide items.
     *
     * @return Builder<T>
     */
    public function viewable(?Authenticatable $user = null);
}
