---
description: Annotate your controllers with PHP attributes to generate rich API documentation.
---

# Annotating Controllers

Documentation is driven entirely by PHP attributes placed on your controllers and their action methods. The generator reads these attributes at command time — no runtime overhead is added to your application.

## Controller-level attributes

### `#[DocumentedResource]`

Place this on the **controller class** to mark it as a named API resource group. Controllers without this attribute are silently skipped.

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
| `name` | `string` | yes | Human-readable group name shown in all output formats. |
| `description` | `string` | no | Short description of the resource group. |
| `prefix` | `string` | no | Optional URI prefix hint for documentation purposes. |

### `#[EndpointResource]`

Place this on the **controller class** alongside `#[DocumentedResource]` to bind the controller to an Eloquent model. The generator uses the model class to resolve the JSON:API resource type and can use it for example payload generation.

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

| Parameter | Type | Required | Description |
|---|---|---|---|
| `resource` | `class-string` | yes | Fully-qualified Eloquent model class name. |

## Method-level attributes

### `#[DocumentedEndpointSection]`

Place this on **controller action methods** to set a custom title and description for the endpoint. When omitted, the generator falls back to the method's PHPDoc summary.

```php
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedEndpointSection;

#[DocumentedEndpointSection(title: 'List Posts', description: 'Returns a paginated list of published posts.')]
public function index(JsonApiResponse $response): JsonApiCollection
{
    return $response->using(Post::class);
}
```

| Parameter | Type | Required | Description |
|---|---|---|---|
| `title` | `string` | no | Short endpoint title (e.g. "List Posts"). |
| `description` | `string` | no | Longer description of what the endpoint does. |

## PHPDoc fallback

If `#[DocumentedEndpointSection]` is absent but the method has a PHPDoc block, the **first paragraph** (before any `@param` / `@return` tags) is used as the description automatically:

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

## Documenting query parameters

Every `*QueryParam` attribute accepts an optional `$description` as its last parameter. All existing usages without the description continue to work unchanged.

These attributes can be placed on the **controller class** (applies to all methods) or on individual **action methods** (applies to that endpoint only). They are all repeatable.

### `#[FilterQueryParam]`

```php
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;

#[FilterQueryParam('title', AllowedFilter::SIMILAR, '*', 'Filter posts by title (partial match)')]
#[FilterQueryParam('status', AllowedFilter::EXACT, ['draft', 'published'], 'Filter by publication status')]
```

| Parameter | Type | Description |
|---|---|---|
| `$attribute` | `string` | The filterable attribute or scope name. |
| `$type` | `int\|array\|null` | Filter operator constant(s) from `AllowedFilter`. |
| `$values` | `mixed` | Allowed values, or `'*'` for any. |
| `$description` | `string` | Human-readable description for the generated docs. |

### `#[SortQueryParam]`

```php
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;

#[SortQueryParam('created_at', AllowedSort::DESCENDANT, 'Sort by creation date, newest first')]
#[SortQueryParam('title', AllowedSort::BOTH, 'Sort by title ascending or descending')]
```

| Parameter | Type | Description |
|---|---|---|
| `$attribute` | `string` | The sortable attribute. |
| `$direction` | `int\|null` | Direction constant from `AllowedSort` (`ASCENDANT`, `DESCENDANT`, `BOTH`). |
| `$description` | `string` | Human-readable description. |

### `#[IncludeQueryParam]`

```php
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;

#[IncludeQueryParam(['tags', 'author'], 'Include related tags and author')]
#[IncludeQueryParam('comments', 'Include post comments')]
```

| Parameter | Type | Description |
|---|---|---|
| `$relationships` | `string\|array` | Relationship name(s) that can be included. |
| `$description` | `string` | Human-readable description. |

### `#[FieldsQueryParam]`

```php
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;

#[FieldsQueryParam('post', ['title', 'body', 'published_at'], 'Limit which post fields are returned')]
```

| Parameter | Type | Description |
|---|---|---|
| `$type` | `string` | The JSON:API resource type (e.g. `'post'`). |
| `$fields` | `array` | The fields that can be requested. |
| `$description` | `string` | Human-readable description. |

### `#[AppendsQueryParam]`

```php
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;

#[AppendsQueryParam('post', ['is_featured', 'reading_time'], 'Append computed accessors to the response')]
```

| Parameter | Type | Description |
|---|---|---|
| `$type` | `string` | The JSON:API resource type. |
| `$attributes` | `array` | The computed attributes that can be appended. |
| `$description` | `string` | Human-readable description. |

### `#[SearchQueryParam]`

```php
use OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam;

#[SearchQueryParam(allowSearch: true, description: 'Full-text search across post content')]
```

| Parameter | Type | Description |
|---|---|---|
| `$allowSearch` | `bool` | Whether fulltext search is available on this endpoint. |
| `$description` | `string` | Human-readable description. |

### `#[SearchFilterQueryParam]`

```php
use OpenSoutheners\LaravelApiable\Attributes\SearchFilterQueryParam;

#[SearchFilterQueryParam('status', ['draft', 'published'], 'Narrow search results by status')]
```

| Parameter | Type | Description |
|---|---|---|
| `$attribute` | `string` | The search filter attribute. |
| `$values` | `mixed` | Allowed values, or `'*'` for any. |
| `$description` | `string` | Human-readable description. |

## Complete annotated controller example

```php
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedEndpointSection;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

#[DocumentedResource(name: 'Posts', description: 'Manage blog posts')]
#[EndpointResource(resource: Post::class)]
class PostController extends Controller
{
    /**
     * Returns a paginated list of published posts.
     */
    #[DocumentedEndpointSection(title: 'List Posts')]
    #[FilterQueryParam('title', AllowedFilter::SIMILAR, '*', 'Filter by title (partial match)')]
    #[FilterQueryParam('status', AllowedFilter::EXACT, ['draft', 'published'], 'Filter by status')]
    #[SortQueryParam('created_at', AllowedSort::DESCENDANT, 'Sort by creation date')]
    #[IncludeQueryParam(['tags', 'author'], 'Include related resources')]
    #[FieldsQueryParam('post', ['title', 'body', 'published_at'], 'Sparse fieldset for posts')]
    #[AppendsQueryParam('post', ['reading_time'], 'Append computed reading time')]
    public function index(JsonApiResponse $response): JsonApiCollection
    {
        return $response->using(Post::class);
    }

    #[DocumentedEndpointSection(title: 'Get Post', description: 'Retrieve a single post by ID.')]
    public function show(Post $post): JsonApiResource
    {
        return Apiable::toJsonApi($post);
    }

    #[DocumentedEndpointSection(title: 'Create Post', description: 'Create a new blog post.')]
    public function store(StorePostRequest $request): JsonApiResource
    {
        $post = Post::create($request->validated());

        return Apiable::toJsonApi($post);
    }
}
```

{% hint style="info" %}
Query param attributes can be placed on the controller class itself to apply them to every action, or on individual methods for fine-grained control. Method-level attributes take precedence.
{% endhint %}
