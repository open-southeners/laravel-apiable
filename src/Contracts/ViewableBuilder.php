<?php

namespace OpenSoutheners\LaravelApiable\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface ViewableBuilder
{
    /**
     * Scope applied to the query for show/hide items.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function viewable(Authenticatable $user = null);
}
