<?php

namespace OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers;

use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultFilter;
use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultSort;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedEndpointSection;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\DefaultSort;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Plan;

/**
 * Fixture controller exercising multi-operator filters, restricted filter values and
 * `#[ApplyDefaultSort]`/`#[ApplyDefaultFilter]`, used to cover the `apiable:types` exporter's
 * operator-merging and default sort/filter handling (attributes not read by the docs
 * generator's `Generator::collectQueryParams()`).
 */
#[DocumentedResource(name: 'Plans', description: 'Manage subscription plans')]
#[EndpointResource(resource: Plan::class)]
class PlansController
{
    #[DocumentedEndpointSection(title: 'List Plans')]
    #[FilterQueryParam(attribute: 'status', type: AllowedFilter::EXACT, values: ['active', 'archived'], description: 'Filter by status')]
    #[FilterQueryParam(attribute: 'created_at', type: [AllowedFilter::GREATER_OR_EQUAL_THAN, AllowedFilter::LOWER_OR_EQUAL_THAN], description: 'Filter by creation date range')]
    #[ApplyDefaultSort(attribute: 'created_at', direction: DefaultSort::DESCENDANT)]
    #[ApplyDefaultFilter(attribute: 'status', operator: AllowedFilter::EXACT, values: 'active')]
    public function index(): void {}
}
