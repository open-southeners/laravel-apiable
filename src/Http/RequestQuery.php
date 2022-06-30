<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder;
use OpenSoutheners\LaravelApiable\Contracts\ViewQueryable;
use function OpenSoutheners\LaravelHelpers\Models\query_from;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestQuery
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Illuminate\Contracts\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Create new class instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->request = app(Request::class);
    }

    /**
     * Create request instance from query or model.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $query
     * @return $this
     */
    public static function create($query)
    {
        return (new self)->setQuery($query);
    }

    /**
     * Set query to the request instance.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $query
     * @return $this
     */
    public function setQuery($query)
    {
        if (is_string($query) && ! class_exists($query)) {
            throw new HttpException(500, "Class '${query}' doesn't exists.");
        }

        if (is_string($query)) {
            $this->query = query_from($query);
        } else {
            $this->query = $query instanceof Model ? $query->newQuery() : $query;
        }

        return $this;
    }

    /**
     * Apply filters if anything found in the request.
     *
     * @return $this
     */
    public function applyFilters()
    {
        if (! $this->request->has('filter')) {
            return $this;
        }

        app(ApplyFiltersToQuery::class)->from($this->request, $this->query);

        return $this;
    }

    /**
     * Build search query with pre-filters.
     *
     * @param  \Laravel\Scout\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\Paginator
     */
    protected function buildSearchQuery($query, Request $request)
    {
        $query->query(function (Builder $query) use ($request) {
            app(ApplyFiltersToQuery::class)->from($request, $query);

            if ($query->getModel() instanceof ViewQueryable || $query instanceof ViewableBuilder) {
                $query->viewable();
            }
        });

        return $this->paginate
            ? $query->simplePaginate($this->limit)
            : $query->take($this->limit)->get();
    }
}
