<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use OpenSoutheners\LaravelApiable\Http\QueryParamsValidator;
use OpenSoutheners\LaravelApiable\Support\Apiable;

trait ValidatesParams
{
    /**
     * Checks if configured to enforce query paramaters validation.
     */
    public function enforcesValidation(): bool
    {
        return (bool) Apiable::config('requests.validate_params');
    }

    /**
     * Get instance of query params validator.
     */
    protected function validator(array $params): QueryParamsValidator
    {
        return new QueryParamsValidator(
            $params,
            $this->enforcesValidation()
        );
    }
}
