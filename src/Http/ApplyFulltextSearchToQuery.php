<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;
use function OpenSoutheners\LaravelHelpers\Classes\class_use;

class ApplyFulltextSearchToQuery implements HandlesRequestQueries
{
    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $request
     * @param \Closure(\OpenSoutheners\LaravelApiable\Http\RequestQueryObject): \Illuminate\Database\Eloquent\Builder $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $request, Closure $next)
    {
        $queryModel = $request->query->getModel();
        $userSearchQuery = $request->searchQuery();

        if (
            ! $request->isSearchAllowed()
            || ! class_use($queryModel, 'Laravel\Scout\Searchable')
            || ! method_exists($queryModel, 'search')
            || empty($userSearchQuery)
        ) {
            return $next($request);
        }

        $scoutBuilder = $queryModel::search($userSearchQuery);

        $this->applyFilters($scoutBuilder, $request->userAllowedSearchFilters());

        $request->query->whereKey($scoutBuilder->keys()->toArray());

        return $next($request);
    }

    /**
     * Apply filters to search query (Scout).
     *
     * @param  \Laravel\Scout\Builder  $query
     * @param  array<string, array>  $searchFilters
     * @return void
     */
    protected function applyFilters($query, array $searchFilters)
    {
        if (empty($searchFilters)) {
            return;
        }

        foreach ($searchFilters as $attribute => $values) {
            if (count($values) > 1) {
                $query->whereIn($attribute, $values);

                continue;
            }

            $query->where($attribute, head($values));
        }
    }
}
