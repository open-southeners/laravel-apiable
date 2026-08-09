<?php

namespace OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers;

use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedEndpointSection;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;

/**
 * Fixture controller exercising repeatable sort/include/appends/fields
 * attributes on the same endpoint, used to cover the docs generator
 * duplicate-param and resource-type-slug fixes.
 */
#[DocumentedResource(name: 'Comments', description: 'Manage post comments')]
#[EndpointResource(resource: Tag::class)]
class CommentsController
{
    #[DocumentedEndpointSection(title: 'List Comments')]
    #[SortQueryParam(attribute: 'created_at', direction: AllowedSort::DESCENDANT, description: 'Sort by creation date')]
    #[SortQueryParam(attribute: 'likes', direction: AllowedSort::ASCENDANT, description: 'Sort by likes')]
    #[IncludeQueryParam(relationships: 'author', description: 'Include the author')]
    #[IncludeQueryParam(relationships: 'post', description: 'Include the parent post')]
    #[AppendsQueryParam(type: Tag::class, attributes: ['excerpt'], description: 'Include computed excerpt')]
    #[FieldsQueryParam(type: Tag::class, fields: ['name'], description: 'Sparse fieldset')]
    public function index(): void {}

    #[DocumentedEndpointSection(title: 'Create Comment')]
    public function store(): void {}
}
