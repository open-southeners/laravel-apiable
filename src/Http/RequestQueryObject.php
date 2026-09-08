<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use OpenSoutheners\FlexUrl\FlexUrl;
use OpenSoutheners\FlexUrl\FlexUrlOptions;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 */
class RequestQueryObject
{
    use Concerns\AllowsAppends;
    use Concerns\AllowsFields;
    use Concerns\AllowsFilters;
    use Concerns\AllowsIncludes;
    use Concerns\AllowsSearch;
    use Concerns\AllowsSorts;
    use Concerns\ValidatesParams;

    /**
     * @var Builder<T>
     */
    public $query;

    /**
     * @var Collection<int|string, mixed>|null
     */
    protected ?Collection $queryParameters = null;

    /**
     * Memoized flex-url parser for the current request's raw query string.
     */
    protected ?FlexUrl $flexUrl = null;

    /**
     * Construct the request query object.
     */
    public function __construct(protected Request $request)
    {
        //
    }

    /**
     * Single parse point for the request's query string (filter/sort/include/fields/appends), backed by flex-url.
     *
     * Fed the raw path + `QUERY_STRING` rather than `$this->request->fullUrl()`: Symfony's `getQueryString()`
     * (behind `fullUrl()`) normalises the query string, re-encoding every comma to `%2C` uniformly before
     * flex-url would ever see it, which would silently defeat `strict_comma_encoding` by merging every
     * multi-value filter into one value.
     */
    public function flexUrl(): FlexUrl
    {
        return $this->flexUrl ??= FlexUrl::from(
            $this->request->getPathInfo().'?'.$this->request->server('QUERY_STRING', ''),
            new FlexUrlOptions(
                strictCommaEncoding: (bool) Apiable::config('requests.strict_comma_encoding'),
            ),
        );
    }

    /**
     * Set query for this request query object.
     *
     * @param  Builder  $query
     */
    public function setQuery($query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get request query parameters as array.
     *
     * @return Collection<int|string, mixed>
     */
    public function queryParameters(): Collection
    {
        if (! $this->queryParameters) {
            $this->queryParameters = Collection::make(
                array_map(
                    [HeaderUtils::class, 'parseQuery'],
                    explode('&', $this->request->server('QUERY_STRING', ''))
                )
            )->groupBy(fn ($item, $key) => head(array_keys($item)), true)
                ->map(fn (Collection $collection) => $collection->flatten(1)->all());
        }

        return $this->queryParameters;
    }

    /**
     * Get the underlying request object.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Allows the following user operations.
     */
    public function allows(
        array $sorts = [],
        array $filters = [],
        array $includes = [],
        array $fields = [],
        array $appends = []
    ): self {
        /** @var array<string, array> $allowedArr */
        $allowedArr = compact('sorts', 'filters', 'includes', 'fields', 'appends');

        foreach ($allowedArr as $allowedKey => $alloweds) {
            foreach ($alloweds as $allowedItem) {
                $allowedItemAsArg = (array) $allowedItem;

                match ($allowedKey) {
                    'sorts' => $this->allowSort(...$allowedItemAsArg),
                    'filters' => $this->allowFilter(...$allowedItemAsArg),
                    'includes' => $this->allowInclude(...$allowedItemAsArg),
                    'fields' => $this->allowFields(...$allowedItemAsArg),
                    'appends' => $this->allowAppends(...$allowedItemAsArg),
                    default => null,
                };
            }
        }

        return $this;
    }

    /**
     * Process query object allowing the following user operations.
     */
    public function allowing(array $alloweds): self
    {
        foreach ($alloweds as $allowed) {
            match (get_class($allowed)) {
                AllowedSort::class => $this->allowSort($allowed),
                AllowedFilter::class => $this->allowFilter($allowed),
                AllowedInclude::class => $this->allowInclude($allowed),
                AllowedFields::class => $this->allowFields($allowed),
                AllowedAppends::class => $this->allowAppends($allowed),
                AllowedSearchFilter::class => $this->allowSearchFilter($allowed),
                default => null,
            };
        }

        return $this;
    }
}
