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

        $requestQueryObject->query->whereKey($queryModel::search($userSearchQuery)->keys()->toArray());

        return $next($requestQueryObject);
    }
}
