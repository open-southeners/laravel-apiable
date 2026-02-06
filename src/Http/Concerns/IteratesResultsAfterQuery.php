<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use OpenSoutheners\LaravelApiable\Http\QueryParamsValidator;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\Support\Apiable;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\JsonApiResponse
 */
trait IteratesResultsAfterQuery
{
    /**
     * Post-process result from query to apply appended attributes.
     *
     * @param  mixed  $result
     * @return mixed
     */
    protected function resultPostProcessing($result)
    {
        $this->addAppendsToResult($result);

        $includeAllowed = is_null($this->includeAllowedToResponse)
            ? Apiable::config('responses.include_allowed')
            : $this->includeAllowedToResponse;

        if ($includeAllowed && $result instanceof JsonApiResource) {
            $result->additional(['meta' => array_filter([
                'allowed_filters' => $this->request->getAllowedFilters(),
                'allowed_sorts' => $this->request->getAllowedSorts(),
            ])]);
        }

        if ($result instanceof JsonApiCollection) {
            $result->withQuery(
                array_filter(
                    $this->getRequest()->query->all(),
                    fn ($queryParam) => $queryParam !== 'page',
                    ARRAY_FILTER_USE_KEY
                )
            );
        }

        return $result;
    }

    /**
     * Add allowed user appends to result.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection|\OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource  $result
     */
    protected function addAppendsToResult($result): void
    {
        $filteredUserAppends = (new QueryParamsValidator(
            $this->request->appends(),
            $this->request->enforcesValidation(),
            $this->request->getAllowedAppends()
        ))->when(
            function ($key, $modifiers, $values, $rules, &$valids) {
                $valids = array_intersect($values, $rules);

                return empty(array_diff($values, $rules));
            },
            fn ($key, $values) => throw new Exception(sprintf('"%s" fields for resource type "%s" cannot be appended', implode(', ', $values), $key))
        )->validate();

        // This are forced by the application owner / developer...
        // So the values are bypassing allowed appends
        if (! empty($this->forceAppends)) {
            $filteredUserAppends = array_merge_recursive($filteredUserAppends, $this->forceAppends);
        }

        if (! empty($filteredUserAppends)) {
            if ($result instanceof JsonApiCollection) {
                $result->collection->each(fn (JsonApiResource $item) => $this->applyAppendsToModel($item->resource, $filteredUserAppends));

                foreach ($result->with['included'] ?? [] as $included) {
                    $this->applyAppendsToModel($included->resource, $filteredUserAppends);
                }
            } elseif ($result instanceof JsonApiResource) {
                $this->applyAppendsToModel($result->resource, $filteredUserAppends);

                foreach ($result->with['included'] ?? [] as $included) {
                    $this->applyAppendsToModel($included->resource, $filteredUserAppends);
                }
            } elseif ($result instanceof Paginator) {
                $result->through(function (mixed $paginatorItem) use ($filteredUserAppends) {
                    $this->applyAppendsToModel($paginatorItem, $filteredUserAppends);

                    return $paginatorItem;
                });
            }
        }
    }

    /**
     * Apply appends to a single model by its resource type.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    private function applyAppendsToModel(mixed $model, array $appends): void
    {
        $resourceType = Apiable::getResourceType($model);

        if ($appendsArr = $appends[$resourceType] ?? null) {
            $model->makeVisible($appendsArr)->append($appendsArr);
        }
    }
}
