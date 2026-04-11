<?php

namespace OpenSoutheners\LaravelApiable;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Http\JsonApiPaginator;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
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
            // @codeCoverageIgnoreStart
            if (class_exists("Hammerstone\FastPaginate\FastPaginate") || class_exists("AaronFrancis\FastPaginate\FastPaginate")) {
                $pageSize ??= $this->getModel()->getPerPage();
                $requestedPageSize = (int) request('page.size', Apiable::config('responses.pagination.default_size'));
                if ($requestedPageSize && (! $pageSize || $requestedPageSize !== $pageSize)) {
                    $pageSize = $requestedPageSize;
                }
                $pageNumberParamName = rawurldecode(Str::beforeLast(Arr::query(Arr::undot([$pageName => ''])), '='));

                return new JsonApiCollection($this->fastPaginate($pageSize, $columns, $pageNumberParamName, request($pageName, 1)));
            }
            // @codeCoverageIgnoreEnd

            return JsonApiPaginator::paginate($this, $pageSize, $columns, $pageName, $page);
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
