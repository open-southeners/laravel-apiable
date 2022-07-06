<?php

namespace OpenSoutheners\LaravelApiable\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class NotJsonApiableModelException
{
    /**
     * Constructs an instance of this exception for model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public static function forModel($model)
    {
        throw new HttpException(500, sprintf('%s cannot be parsed to JSON:API', get_class($model)));
    }
}
