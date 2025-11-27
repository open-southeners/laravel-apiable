<?php

namespace OpenSoutheners\LaravelApiable;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
         * @param  array<string>  $columns
         * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection
         */
        return function (null|int|string $pageSize = null, array $columns = ['*'], string $pageName = 'page.number', ?int $page = null) {
            $page ??= request($pageName, 1);
            $pageSize ??= $this->getModel()->getPerPage();
            $requestedPageSize = (int) request('page.size', Apiable::config('responses.pagination.default_size'));

            if ($requestedPageSize && (! $pageSize || $requestedPageSize !== $pageSize)) {
                $pageSize = $requestedPageSize;
            }

            /**
             * FIXME: This is needed as Laravel is very inconsistent, request get is using dots 
             * while paginator doesn't represent them...
             */
            $pageNumberParamName = Str::beforeLast(Arr::query(Arr::undot([$pageName => ''])), '=');

            // @codeCoverageIgnoreStart
            if (class_exists("Hammerstone\FastPaginate\FastPaginate") || class_exists("AaronFrancis\FastPaginate\FastPaginate")) {
                return Apiable::toJsonApi(
                    $this->fastPaginate($pageSize, $columns, $pageNumberParamName, $page)
                );
            }
            // @codeCoverageIgnoreEnd

            $results = ($total = $this->toBase()->getCountForPagination())
                ? $this->forPage($page, $pageSize)->get($columns)
                : $this->getModel()->newCollection();

            return Apiable::toJsonApi($this->paginator($results, $total, $pageSize, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageNumberParamName,
            ]));
        };
    }

    public function hasJoin()
    {
        /**
         * Check whether join is already on the query instance.
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
