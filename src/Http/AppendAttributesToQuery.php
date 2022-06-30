<?php

namespace OpenSoutheners\LaravelApiable\Http;

use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;

class AppendAttributesToQuery implements HandlesRequestQueries
{
    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $allowed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(Request $request, Builder $query, array $allowed = ['*'])
    {
        if (empty($allowed)) {
            return $query;
        }

        $filters = Collection::make($request->all());

        if ($allowed[0] === '*') {
            return $this->applyFilters($query, $filters);
        }

        return $query;
    }
}
