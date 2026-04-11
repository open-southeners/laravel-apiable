<?php

namespace OpenSoutheners\LaravelApiable\Tests\Fixtures\Controllers;

use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedEndpointSection;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;

#[DocumentedResource(name: 'Posts', description: 'Manage blog posts')]
#[EndpointResource(resource: Post::class)]
class PostsController
{
    /**
     * Get a paginated list of posts.
     *
     * Returns all published posts, ordered by creation date.
     *
     * @return void
     */
    #[DocumentedEndpointSection(title: 'List Posts', description: 'Get a paginated list of posts')]
    #[FilterQueryParam(attribute: 'title', type: AllowedFilter::SIMILAR, description: 'Filter by title')]
    #[SortQueryParam(attribute: 'created_at', direction: AllowedSort::DESCENDANT, description: 'Sort by creation date')]
    #[IncludeQueryParam(relationships: ['tags', 'author'], description: 'Include relationships')]
    public function index(): void {}

    /**
     * Get a single post by ID.
     */
    #[DocumentedEndpointSection(title: 'Get Post')]
    public function show(): void {}
}
