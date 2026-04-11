---
description: >-
  Generate API documentation from your annotated controllers — Postman
  collections, Markdown/MDX pages, and OpenAPI 3.1 YAML — with a single
  Artisan command.
---

# Generating Documentation

The `apiable:docs` command introspects your routes and controller attributes to produce API documentation in three formats: **Postman v2.1**, **Markdown / MDX**, and **OpenAPI 3.1 YAML**.

## Annotating controllers

Documentation is driven entirely by PHP Attributes placed on your controllers. Controllers without a `#[DocumentedResource]` attribute are silently skipped, so you opt in only where you want.

### `#[DocumentedResource]`

Place this on the **controller class** to mark it as a named API resource group.

```php
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;

#[DocumentedResource(name: 'Posts', description: 'Create, read, update and delete blog posts')]
class PostController extends Controller
{
    // ...
}
```

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | yes | Human-readable group name shown in all output formats |
| `description` | `string` | no | Short description of the resource group |
| `prefix` | `string` | no | Optional URI prefix hint for documentation purposes |

### `#[DocumentedEndpointSection]`

Place this on **controller methods** to set a custom title and description for each endpoint. When omitted, the command falls back to the method's PHPDoc summary.

```php
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedEndpointSection;

#[DocumentedEndpointSection(title: 'List Posts', description: 'Returns a paginated list of published posts.')]
public function index(JsonApiResponse $response): JsonApiCollection
{
    return $response->using(Post::class);
}
```

If you skip the attribute but write a PHPDoc block, the **first paragraph** (before any `@param`/`@return` tags) is used as the description automatically:

```php
/**
 * Returns a paginated list of published posts.
 *
 * Supports filtering, sorting and sparse fieldsets via query parameters.
 *
 * @return JsonApiCollection
 */
public function index(JsonApiResponse $response): JsonApiCollection
{
    return $response->using(Post::class);
}
```

### `#[EndpointResource]`

Place this on the **controller class** alongside `#[DocumentedResource]` to bind the controller to an Eloquent model. The generator uses this to resolve the JSON:API resource type for example payloads.

```php
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;

#[DocumentedResource(name: 'Posts', description: 'Manage blog posts')]
#[EndpointResource(resource: Post::class)]
class PostController extends Controller
{
    // ...
}
```

### Documenting query parameters

Every `*QueryParam` attribute accepts an optional `$description` as its **last** parameter. Existing usages without the description keep working unchanged.

```php
#[FilterQueryParam('title', AllowedFilter::SIMILAR, '*', 'Filter posts by title (partial match)')]
#[SortQueryParam('created_at', AllowedSort::DESCENDANT, 'Sort by creation date')]
#[IncludeQueryParam(['tags', 'author'], 'Include related resources')]
#[FieldsQueryParam('post', ['title', 'body'], 'Limit returned fields')]
#[AppendsQueryParam('post', ['is_featured'], 'Append computed accessors')]
public function index(JsonApiResponse $response): JsonApiCollection
{
    return $response->using(Post::class);
}
```

A complete annotated controller example:

```php
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedEndpointSection;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;

#[DocumentedResource(name: 'Posts', description: 'Manage blog posts')]
#[EndpointResource(resource: Post::class)]
class PostController extends Controller
{
    /**
     * Returns a paginated list of published posts.
     */
    #[DocumentedEndpointSection(title: 'List Posts')]
    #[FilterQueryParam('title', AllowedFilter::SIMILAR, '*', 'Filter by title')]
    #[SortQueryParam('created_at', AllowedSort::DESCENDANT, 'Sort by creation date')]
    #[IncludeQueryParam(['tags', 'author'], 'Include relationships')]
    public function index(JsonApiResponse $response): JsonApiCollection
    {
        return $response->using(Post::class);
    }

    #[DocumentedEndpointSection(title: 'Get Post', description: 'Retrieve a single post by ID.')]
    public function show(Post $post): JsonApiResource
    {
        return Apiable::toJsonApi($post);
    }
}
```

## Running the command

```bash
php artisan apiable:docs
```

When no `--format` flag is provided the command checks `documentation.default_format` in the config file; if that is also unset it presents an interactive prompt.

### Options

| Option | Default | Description |
|---|---|---|
| `--format` | _(config or prompt)_ | Output format: `markdown`, `postman`, `openapi`. Repeatable for multiple formats in one run. |
| `--stub` | `protocol` | Markdown stub to use: `protocol` (Tailwind Protocol MDX) or `plain` (portable Markdown). |
| `--only` | — | Only include routes matching these glob patterns (repeatable). |
| `--exclude` | — | Exclude routes matching these patterns, merged with `documentation.excluded_routes` from config. |
| `--path` | _(config or storage)_ | Override the output directory. |

### Examples

```bash
# Default format from config
php artisan apiable:docs

# Single format
php artisan apiable:docs --format=markdown

# Multiple formats in one run
php artisan apiable:docs --format=markdown --format=postman

# Plain Markdown (GitHub-friendly)
php artisan apiable:docs --format=markdown --stub=plain

# OpenAPI only for /api/* routes
php artisan apiable:docs --format=openapi --only="api/*"

# Custom output path
php artisan apiable:docs --format=postman --path=./docs/api
```

## Output formats

### Postman v2.1 collection

Produces a single `postman_collection.json` importable into Postman or Insomnia. Features:

- Collection-level `auth` block (Bearer or Basic) derived from the first detected auth scheme.
- Per-endpoint `Authorization` header added automatically for protected routes.
- `Accept: application/vnd.api+json` header on every request.
- Query parameters listed with keys, example values, and descriptions.

### Markdown / MDX

Produces one `.mdx` (Protocol stub) or `.md` (plain stub) file per resource group, named after the resource (e.g. `posts.mdx`).

Two stubs ship with the package:

| Stub | Extension | Best for |
|---|---|---|
| `protocol` _(default)_ | `.mdx` | [Tailwind Protocol](https://tailwindui.com/templates/protocol) documentation sites |
| `plain` | `.md` | GitHub, GitLab, Bitbucket, or any static site generator |

### OpenAPI 3.1 YAML

Produces a single `openapi.yaml` that validates against the OpenAPI 3.1 specification. Features:

- One `paths` entry per documented route with `parameters` for every query param.
- `components.securitySchemes` populated from detected auth middleware.
- Per-operation `security` requirement added automatically.
- Uses `symfony/yaml` (bundled with Laravel 12+) for output.

## Authentication detection

When `documentation.auth.detect_middleware` is `true` (the default), the generator inspects each route's middleware list. Matches against `documentation.auth.middleware_map` produce auth annotations in all output formats.

The default map covers the most common cases:

```php
'middleware_map' => [
    'auth:sanctum' => 'bearer',
    'auth:api'     => 'bearer',
    'auth.basic'   => 'basic',
],
```

Add your own entries to the map for custom guards or packages.

## Configuration

After publishing the config file (`php artisan vendor:publish --tag=config`), you will find a `documentation` section at the bottom of `config/apiable.php`:

```php
'documentation' => [

    // Where generated files are written. Defaults to storage/exports/apiable.
    'output_path' => null,

    // Default format when --format is not supplied: markdown | postman | openapi
    'default_format' => 'markdown',

    // Default Markdown stub: protocol | plain
    'default_stub' => 'protocol',

    // Routes whose URIs match these glob patterns are excluded from output.
    'excluded_routes' => [
        '_debugbar/*',
        '_ignition/*',
        'nova-api/*',
        'nova/*',
        'nova',
        'telescope*',
        'horizon*',
    ],

    'auth' => [
        // Auto-detect auth scheme from route middleware.
        'detect_middleware' => true,

        // Map middleware names to auth scheme types (bearer | basic).
        'middleware_map' => [
            'auth:sanctum' => 'bearer',
            'auth:api'     => 'bearer',
            'auth.basic'   => 'basic',
        ],
    ],

],
```

## Customising stubs

Publish the package stubs to your application so you can modify the Markdown templates:

```bash
php artisan vendor:publish --tag=apiable-stubs
```

This copies the stubs to `stubs/apiable/docs/` inside your application:

```
stubs/
└── apiable/
    └── docs/
        ├── protocol.mdx   ← Tailwind Protocol MDX template
        └── plain.md       ← Plain Markdown template
```

The command always checks for user-published stubs first. If it finds one it uses it; otherwise it falls back to the version bundled with the package.

The stubs are compiled with Laravel Blade, so you can use any Blade directive. Each stub receives a `$resource` array:

```php
[
    'name'        => 'Posts',
    'description' => 'Manage blog posts',
    'modelClass'  => 'App\\Models\\Post',  // null if #[EndpointResource] is absent
    'endpoints'   => [
        [
            'uri'         => 'posts',
            'method'      => 'GET',
            'title'       => 'List Posts',
            'description' => 'Returns a paginated list of published posts.',
            'auth'        => ['type' => 'bearer', 'middleware' => 'auth:sanctum'],  // null if unauthenticated
            'queryParams' => [
                [
                    'key'         => 'filter[title][like]',
                    'kind'        => 'filter',
                    'description' => 'Filter by title',
                    'values'      => '*',
                    'required'    => false,
                ],
                // ...
            ],
        ],
        // ...
    ],
]
```
