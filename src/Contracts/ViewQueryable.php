<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 */
interface ViewQueryable
{
    /**
     * Scope applied to the query for show/hide items.
     *
     * @param  Builder<T>  $query
     * @return void
     */
    public function scopeViewable(Builder $query, ?Authenticatable $user = null);
}
