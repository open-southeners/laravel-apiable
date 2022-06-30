<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface ViewableBuilder
{
    /**
     * Scope applied to the query for show/hide items.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function viewable(?Authenticatable $user = null);
}
