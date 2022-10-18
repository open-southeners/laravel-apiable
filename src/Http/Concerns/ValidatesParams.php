<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use OpenSoutheners\LaravelApiable\Http\QueryParamsValidator;
use OpenSoutheners\LaravelApiable\Support\Apiable;

trait ValidatesParams
{
    /**
     * Checks if configured to enforce query paramaters validation.
     *
     * @return bool
     */
    public function enforcesValidation()
    {
        return (bool) Apiable::config('requests.validate_params');
    }

    /**
     * Get instance of query params validator.
     *
     * @param  array  $params
     * @return \OpenSoutheners\LaravelApiable\Http\QueryParamsValidator
     */
    protected function validator(array $params)
    {
        return new QueryParamsValidator(
            $params,
            $this->enforcesValidation()
                ? QueryParamsValidator::ENFORCE_VALIDATION_STRATEGY
                : QueryParamsValidator::FILTER_VALIDS_ONLY_STRATEGY
        );
    }
}
