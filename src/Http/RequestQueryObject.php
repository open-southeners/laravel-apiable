<?php

namespace OpenSoutheners\LaravelApiable\Http;

class RequestQueryObject
{
    use Concerns\AllowsAppends;
    use Concerns\AllowsFields;
    use Concerns\AllowsFilters;
    use Concerns\AllowsIncludes;
    use Concerns\AllowsSorts;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    public $query;

    /**
     * Construct the request query object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function __construct($request, $query)
    {
        $this->request = $request;
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    /**
     * Process query object allowing the following user operations.
     *
     * @param  array  $alloweds
     * @return $this
     */
    public function allowing(array $alloweds)
    {
        foreach ($alloweds as $allowed) {
            match (get_class($allowed)) {
                AllowedSort::class => $this->allowSort($allowed),
                AllowedFilter::class => $this->allowFilter($allowed),
                AllowedInclude::class => $this->allowInclude($allowed),
                AllowedFields::class => $this->allowFields($allowed),
                AllowedAppends::class => $this->allowAppends($allowed),
            };
        }

        return $this;
    }
}
