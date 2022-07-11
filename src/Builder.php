<?php

namespace OpenSoutheners\LaravelApiable;

use Illuminate\Pagination\Paginator;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Builder
{
    public function jsonApiPaginate()
    {
        /**
         * Paginate the given query using JSON:API.
         *
         * @param  int|string  $perPage
         * @param  array  $columns
         * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
         */
        return function ($pageSize = null, $columns = ['*'], $page = null) {
            $pageName = 'page[number]';
            $page = $page ?: Paginator::resolveCurrentPage($pageName);
            $pageSize = $pageSize ?: $this->model->getPerPage();
            $requestedPageSize = (int) request('page.size', Apiable::config('pagination.default_size'));

            if (! $pageSize || $pageSize < $requestedPageSize) {
                $pageSize = $requestedPageSize;
            }

            $results = ($total = $this->toBase()->getCountForPagination())
                ? $this->forPage($page, $pageSize)->get($columns)
                : $this->model->newCollection();

            return Apiable::toJsonApi($this->paginator($results, $total, $pageSize, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]));
        };
    }
}
