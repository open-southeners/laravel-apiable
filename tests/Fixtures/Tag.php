<?php

namespace OpenSoutheners\LaravelApiable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Concerns\HasJsonApi;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\JsonApiableOptions;

class Tag extends Model implements JsonApiable
{
    use HasJsonApi;

    /**
     * The attributes that should be visible in serialization.
     *
     * @var string[]
     */
    protected $visible = ['name', 'slug'];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * Set options for model to be serialize with JSON:API.
     *
     * @return \OpenSoutheners\LaravelApiable\JsonApiableOptions
     */
    public function jsonApiableOptions()
    {
        return JsonApiableOptions::withDefaults(self::class)
            ->resourceType('label');
    }
}
