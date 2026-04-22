<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

class JsonApiPaginator
{
    /**
     * Paginate the given Eloquent builder using JSON:API conventions.
     *
     * @param  array<string>  $columns
     * @param  class-string<\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource>|null  $resourceClass
     */
    public static function paginate(
        Builder $builder,
        null|int|string $pageSize = null,
        array $columns = ['*'],
        string $pageName = 'page.number',
        ?int $page = null,
        ?string $resourceClass = null,
    ): JsonApiCollection {
        $page ??= request($pageName, 1);
        $pageSize ??= $builder->getModel()->getPerPage();
        $requestedPageSize = (int) request('page.size', Apiable::config('responses.pagination.default_size'));

        if ($requestedPageSize && (! $pageSize || $requestedPageSize !== $pageSize)) {
            $pageSize = $requestedPageSize;
        }

        /**
         * FIXME: This is needed as Laravel is very inconsistent, request get is using dots
         * while paginator doesn't represent them...
         */
        $pageNumberParamName = rawurldecode(Str::beforeLast(Arr::query(Arr::undot([$pageName => ''])), '='));

        $results = ($total = $builder->toBase()->getCountForPagination())
            ? $builder->forPage($page, $pageSize)->get($columns)
            : $builder->getModel()->newCollection();

        return new JsonApiCollection(Container::getInstance()->makeWith(LengthAwarePaginator::class, [
            'items' => $results,
            'total' => $total,
            'perPage' => $pageSize,
            'currentPage' => $page,
            'options' => [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageNumberParamName,
            ],
        ]), $resourceClass);
    }
}
