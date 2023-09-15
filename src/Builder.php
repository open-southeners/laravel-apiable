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
         * @param  int|string  $pageSize
         * @param  array  $columns
         * @param  int  $page
         * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
         */
        return function ($pageSize = null, $columns = ['*'], $page = null) {
            $pageName = 'page[number]';
            $page = $page ?: Paginator::resolveCurrentPage($pageName);
            $pageSize = $pageSize ?: $this->model->getPerPage();
            $requestedPageSize = (int) request('page.size', Apiable::config('responses.pagination.default_size'));

            if ($requestedPageSize && (! $pageSize || $requestedPageSize !== $pageSize)) {
                $pageSize = $requestedPageSize;
            }

            // @codeCoverageIgnoreStart
            if (class_exists("Hammerstone\FastPaginate\FastPaginate")) {
                return Apiable::toJsonApi(
                    $this->fastPaginate($pageSize, $columns, 'page[number]', (int) request('page.number'))
                );
            }
            // @codeCoverageIgnoreEnd

            $results = ($total = $this->toBase()->getCountForPagination())
                ? $this->forPage($page, $pageSize)->get($columns)
                : $this->model->newCollection();

            return Apiable::toJsonApi($this->paginator($results, $total, $pageSize, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]));
        };
    }

    public function hasJoin()
    {
        /**
         * Check wether join is already on the query instance.
         *
         * @param  string  $joinTable
         * @return bool
         */
        return function ($joinTable) {
            $joins = $this->getQuery()->joins;

            if ($joins === null) {
                return false;
            }

            foreach ($joins as $join) {
                if ($join->table === $joinTable) {
                    return true;
                }
            }

            return false;
        };
    }
}
