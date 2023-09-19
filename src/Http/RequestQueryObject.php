<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
     * @var \Illuminate\Database\Eloquent\Builder<T>
     */
    public $query;

    /**
     * @var \Illuminate\Support\Collection<(int|string), array<int, mixed>>|null
     */
    protected ?Collection $queryParameters = null;

    /**
     * Construct the request query object.
     */
    public function __construct(protected Request $request)
    {
        //
    }

    /**
     * Set query for this request query object.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function setQuery($query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get request query parameters as array.
     *
     * @return \Illuminate\Support\Collection<array>
     */
    public function queryParameters(): Collection
    {
        if (! $this->queryParameters) {
            $queryParameters = array_filter(
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
