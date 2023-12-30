<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

interface ViewQueryable
{
    /**
     * Scope applied to the query for show/hide items.
     *
     * @return void
     */
    public function scopeViewable(Builder $query, Authenticatable $user = null);
}
