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
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $requestQueryObject
     * @param  \Closure  $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $requestQueryObject, Closure $next)
    {
        $queryModel = $requestQueryObject->query->getModel();
        $userSearchQuery = $requestQueryObject->searchQuery();

        if (
            ! $requestQueryObject->isSearchAllowed()
            || ! class_use($queryModel, 'Laravel\Scout\Searchable')
            || empty($userSearchQuery)
        ) {
            return $next($requestQueryObject);
        }

        $scoutBuilder = $queryModel::search($userSearchQuery);

        // TODO: Search filters & sorts
        // if ($requestQueryObject->hasSearchFilters()) {
        //     $scoutBuilder->where('');
        // }

        $requestQueryObject->query->whereKey($scoutBuilder->keys()->toArray());

        return $next($requestQueryObject);
    }

    protected function applyFilters(RequestQueryObject $request)
    {
        $allowedFilters = $request->getAllowedSearchFilters();

        $userFilters = $request->filters();
    }

    protected function applySorts()
    {
        // code...
    }
}
