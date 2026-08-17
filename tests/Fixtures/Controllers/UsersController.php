<?php

namespace OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers;

use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedEndpointSection;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;

/**
 * Fixture controller with no query-param attributes at all (e.g. a fluent-style
 * controller that configures allowances via `->allowing()`), used to cover the docs/types
 * generators' "still emits an entry with empty allowances" behaviour for attribute-only
 * reflection.
 */
#[DocumentedResource(name: 'Users', description: 'Manage users')]
#[EndpointResource(resource: User::class)]
class UsersController
{
    #[DocumentedEndpointSection(title: 'List Users')]
    public function index(): void {}
}
