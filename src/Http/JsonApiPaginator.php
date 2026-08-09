<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

class JsonApiPaginator
{
    /**
     * Paginate the given Eloquent builder using JSON:API conventions.
     *
     * Dispatches to the strategy set by `apiable.responses.pagination.type`
     * (length-aware, simple or cursor).
     *
     * @param  array<string>  $columns
     * @param  class-string<JsonApiResource>|null  $resourceClass
     */
    public static function paginate(
        Builder $builder,
        null|int|string $pageSize = null,
        array $columns = ['*'],
        string $pageName = 'page.number',
        ?int $page = null,
        ?string $resourceClass = null,
    ): JsonApiCollection {
        return match (Apiable::config('responses.pagination.type')) {
            'simple' => static::simple($builder, $pageSize, $columns, $resourceClass),
            'cursor' => static::cursor($builder, $pageSize, $columns, $resourceClass),
            default => static::lengthAware($builder, $pageSize, $columns, $pageName, $page, $resourceClass),
        };
    }

    /**
     * Paginate the given Eloquent builder executing a `COUNT` query beforehand, returning
     * the full pagination metadata (total item count, last page, page links).
     *
     * @param  array<string>  $columns
     * @param  class-string<JsonApiResource>|null  $resourceClass
     */
    protected static function lengthAware(
        Builder $builder,
        null|int|string $pageSize = null,
        array $columns = ['*'],
        string $pageName = 'page.number',
        ?int $page = null,
        ?string $resourceClass = null,
    ): JsonApiCollection {
        $page ??= request($pageName, 1);
        $pageSize = static::resolvePageSize($builder, $pageSize);

        $pageNumberParamName = static::bracketParamName($pageName);

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

    /**
     * Paginate the given Eloquent builder without a `COUNT` query, only knowing whether
     * a next (or previous) page exists.
     *
     * @param  array<string>  $columns
     * @param  class-string<JsonApiResource>|null  $resourceClass
     */
    protected static function simple(
        Builder $builder,
        null|int|string $pageSize = null,
        array $columns = ['*'],
        ?string $resourceClass = null,
    ): JsonApiCollection {
        $pageName = 'page.number';
        $page = request($pageName, 1);
        $pageSize = static::resolvePageSize($builder, $pageSize);

        $pageNumberParamName = static::bracketParamName($pageName);

        return new JsonApiCollection(
            $builder->simplePaginate($pageSize, $columns, $pageNumberParamName, $page),
            $resourceClass
        );
    }

    /**
     * Paginate the given Eloquent builder using an opaque cursor instead of an offset,
     * avoiding `OFFSET` queries entirely. Requires the query to be sorted by a unique,
     * sequential column (falls back to the model's primary key when none is set).
     *
     * @param  array<string>  $columns
     * @param  class-string<JsonApiResource>|null  $resourceClass
     */
    protected static function cursor(
        Builder $builder,
        null|int|string $pageSize = null,
        array $columns = ['*'],
        ?string $resourceClass = null,
    ): JsonApiCollection {
        $cursorName = 'page.cursor';
        $cursor = CursorPaginator::resolveCurrentCursor($cursorName);
        $pageSize = static::resolvePageSize($builder, $pageSize);

        $cursorParamName = static::bracketParamName($cursorName);

        return new JsonApiCollection(
            $builder->cursorPaginate($pageSize, $columns, $cursorParamName, $cursor),
            $resourceClass
        );
    }

    /**
     * Resolve the effective page size, honouring the `page[size]` request parameter and
     * falling back to `responses.pagination.default_size` before the model's own default.
     */
    protected static function resolvePageSize(Builder $builder, null|int|string $pageSize): int
    {
        $pageSize ??= $builder->getModel()->getPerPage();
        $requestedPageSize = (int) request('page.size', Apiable::config('responses.pagination.default_size'));

        if ($requestedPageSize && (! $pageSize || $requestedPageSize !== $pageSize)) {
            $pageSize = $requestedPageSize;
        }

        return (int) $pageSize;
    }

    /**
     * Convert a dot-notated page param name (used to read the value off the request) into
     * its bracket-notation equivalent (used to build pagination links).
     *
     * FIXME: This is needed as Laravel is very inconsistent, request get is using dots
     * while paginator doesn't represent them...
     */
    protected static function bracketParamName(string $dotParamName): string
    {
        return rawurldecode(Str::beforeLast(Arr::query(Arr::undot([$dotParamName => ''])), '='));
    }
}
